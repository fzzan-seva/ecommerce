<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An order whose receipt has actually been stored on the private disk.
     */
    private function makeOrderWithProof($owner, string $storedAs = 'payment-proofs/receipt.png'): Order
    {
        Storage::fake('payment_proofs');

        Storage::disk('payment_proofs')->put(
            $storedAs,
            file_get_contents(base_path('tests/fixtures/pixel.png')),
        );

        return Order::create([
            'order_number' => 'ORD-'.strtoupper(uniqid()),
            'user_id' => $owner->id,
            'recipient_name' => 'Test Customer',
            'phone' => '081234567890',
            'shipping_address' => 'Jalan Test No. 1, Bandung, Jawa Barat 40111',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
            'payment_proof' => $storedAs,
        ]);
    }

    public function test_the_owner_can_view_their_payment_proof(): void
    {
        $customer = $this->makeCustomer(false);
        $order = $this->makeOrderWithProof($customer);

        $response = $this->actingAs($customer)->get(route('payment-proofs.show', $order));

        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_only_the_owner_or_an_admin_can_view_a_proof(): void
    {
        $customer = $this->makeCustomer(false);
        $order = $this->makeOrderWithProof($customer);

        $other = $this->makeCustomer(false);
        $this->actingAs($other)->get(route('payment-proofs.show', $order))->assertForbidden();

        $admin = $this->makeAdmin(false);
        $this->actingAs($admin)->get(route('payment-proofs.show', $order))->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $customer = $this->makeCustomer(false);
        $order = $this->makeOrderWithProof($customer);

        $this->get(route('payment-proofs.show', $order))->assertRedirect('/login');
    }

    public function test_an_order_without_a_proof_returns_404(): void
    {
        $customer = $this->makeCustomer(false);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'recipient_name' => 'Test Customer',
            'phone' => '081234567890',
            'shipping_address' => 'Jalan Test No. 1, Bandung',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
        ]);

        $this->actingAs($customer)->get(route('payment-proofs.show', $order))->assertNotFound();
    }

    public function test_a_missing_file_returns_404(): void
    {
        $customer = $this->makeCustomer(false);
        $order = $this->makeOrderWithProof($customer, 'payment-proofs/ghost.png');

        Storage::disk('payment_proofs')->delete('payment-proofs/ghost.png');

        $this->actingAs($customer)->get(route('payment-proofs.show', $order))->assertNotFound();
    }

    public function test_paths_outside_the_payment_proofs_directory_are_rejected(): void
    {
        Storage::fake('payment_proofs');
        $customer = $this->makeCustomer(false);

        // A readable file one level up must never be served, even if the
        // database value points at it with a ../ traversal.
        Storage::disk('payment_proofs')->put('secret.txt', 'top secret');
        Storage::disk('payment_proofs')->put(
            'payment-proofs/receipt.png',
            file_get_contents(base_path('tests/fixtures/pixel.png')),
        );

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'recipient_name' => 'Test Customer',
            'phone' => '081234567890',
            'shipping_address' => 'Jalan Test No. 1, Bandung',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
            'payment_proof' => 'payment-proofs/../secret.txt',
        ]);

        $this->actingAs($customer)->get(route('payment-proofs.show', $order))->assertNotFound();
    }

    public function test_a_non_image_file_is_never_served(): void
    {
        Storage::fake('payment_proofs');
        $customer = $this->makeCustomer(false);

        Storage::disk('payment_proofs')->put('payment-proofs/shell.php', '<?php echo "pwned";');

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'recipient_name' => 'Test Customer',
            'phone' => '081234567890',
            'shipping_address' => 'Jalan Test No. 1, Bandung',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
            'payment_proof' => 'payment-proofs/shell.php',
        ]);

        // The content-type is sniffed from the bytes, not trusted from the
        // stored extension, so an executable is never announced as an image.
        $this->actingAs($customer)->get(route('payment-proofs.show', $order))->assertNotFound();
    }
}
