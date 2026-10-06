<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A complete, valid settings payload; individual tests override parts of it.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'My Test Store',
            'tagline' => 'Everything for everyone',
            'description' => 'A store used by the test suite.',
            'phone' => '081200000000',
            'email' => 'store@example.com',
            'address' => 'Jalan Store No. 1, Bandung',
            'currency' => 'IDR',
            'currency_symbol' => 'Rp',
            'currency_decimals' => 0,
            'shipping_cost' => 15000,
            'whatsapp' => '628120000000',
            'instagram' => 'mystore',
            'facebook' => '',
            'payment_methods' => [
                [
                    'key' => 'bank_transfer',
                    'label' => 'Bank Transfer',
                    'type' => 'bank',
                    'account' => '111122223333',
                    'account_name' => 'My Test Store',
                    'enabled' => 1,
                ],
            ],
        ], $overrides);
    }

    public function test_an_admin_can_save_store_identity_and_it_applies_everywhere(): void
    {
        $this->makeAdmin();
        $this->makeProduct(['name' => 'Branded Item', 'price' => 250000]);

        $this->put('/admin/pengaturan', $this->settingsPayload([
            'name' => 'Acme Outfitters',
            'currency_symbol' => '$',
            'currency_decimals' => 2,
        ]))->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseHas('settings', ['key' => 'name', 'value' => 'Acme Outfitters']);
        $this->assertSame('Acme Outfitters', shop()->name());

        // Branding and price formatting reach the storefront.
        $this->get('/')
            ->assertOk()
            ->assertSee('Acme Outfitters')
            ->assertSee('$ 250,000.00');
    }

    public function test_shipping_cost_from_settings_is_used_at_checkout(): void
    {
        $this->makeAdmin();

        $this->put('/admin/pengaturan', $this->settingsPayload(['shipping_cost' => 25000]))
            ->assertRedirect(route('admin.settings.edit'));

        Storage::fake('payment_proofs');

        $product = $this->makeProduct(['price' => 120000], [['M', 'Black', 5]]);
        $customer = $this->makeCustomer(false);
        $address = $this->makeAddress($customer);

        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer);

        $this->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
            'payment_proof' => $this->imageFixture(),
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('25000.00', (string) $order->shipping_cost);
        $this->assertSame('145000.00', (string) $order->total);
    }

    public function test_disabled_payment_methods_disappear_from_checkout_and_are_rejected(): void
    {
        $this->makeAdmin();

        $this->put('/admin/pengaturan', $this->settingsPayload([
            'payment_methods' => [
                ['key' => 'bank_transfer', 'label' => 'Bank Transfer', 'type' => 'bank', 'account' => '111122223333', 'account_name' => 'Store', 'enabled' => 1],
                ['key' => 'e_wallet', 'label' => 'E-Wallet', 'type' => 'e-wallet', 'account' => '999988887777', 'account_name' => 'Store', 'enabled' => 0],
            ],
        ]))->assertRedirect(route('admin.settings.edit'));

        $product = $this->makeProduct([], [['M', 'Black', 5]]);
        $customer = $this->makeCustomer(false);
        $address = $this->makeAddress($customer);

        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer);

        // The checkout form offers the enabled method only.
        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Bank Transfer')
            ->assertDontSee('E-Wallet', false);

        // And a disabled method is rejected by validation even if posted.
        $this->from('/checkout')->post('/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'e_wallet',
            'payment_proof' => $this->imageFixture(),
        ])->assertSessionHasErrors('payment_method');
    }

    public function test_invalid_payment_method_keys_are_rejected(): void
    {
        $this->makeAdmin();

        // Uppercase letters and dashes are not allowed in a method key.
        $this->from(route('admin.settings.edit'))->put('/admin/pengaturan', $this->settingsPayload([
            'payment_methods' => [
                ['key' => 'Bank-Transfer', 'label' => 'Bank Transfer', 'type' => 'bank', 'account' => '1111', 'account_name' => 'Store', 'enabled' => 1],
            ],
        ]))->assertSessionHasErrors('payment_methods.0.key');

        // Duplicate keys would silently overwrite each other in the array.
        $this->from(route('admin.settings.edit'))->put('/admin/pengaturan', $this->settingsPayload([
            'payment_methods' => [
                ['key' => 'bank_transfer', 'label' => 'Bank Transfer', 'type' => 'bank', 'account' => '1111', 'account_name' => 'Store', 'enabled' => 1],
                ['key' => 'bank_transfer', 'label' => 'Bank Transfer 2', 'type' => 'bank', 'account' => '2222', 'account_name' => 'Store', 'enabled' => 1],
            ],
        ]))->assertSessionHasErrors('payment_methods');

        // Neither rejected request may have written anything to the database.
        $this->assertSame(0, Setting::count());
    }

    public function test_required_settings_must_be_present(): void
    {
        $this->makeAdmin();

        $this->from(route('admin.settings.edit'))
            ->put('/admin/pengaturan', $this->settingsPayload(['name' => '', 'currency' => '']))
            ->assertSessionHasErrors(['name', 'currency']);
    }

    public function test_the_logo_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('public');
        $this->makeAdmin();

        $this->put('/admin/pengaturan', $this->settingsPayload([
            'logo' => $this->imageFixture('logo.png'),
        ]))->assertRedirect(route('admin.settings.edit'));

        $logo = shop()->logo();
        $this->assertNotNull($logo);
        Storage::disk('public')->assertExists($logo);
        $this->get('/')->assertSee($logo);

        // Replacing the upload removes the previous file.
        $this->put('/admin/pengaturan', $this->settingsPayload([
            'logo' => $this->imageFixture('logo2.png'),
        ]));

        Storage::disk('public')->assertMissing($logo);
        Storage::disk('public')->assertExists(shop()->logo());

        // Removing it clears both the setting and the file.
        $current = shop()->logo();
        $this->put('/admin/pengaturan', $this->settingsPayload(['remove_logo' => 1]));

        $this->assertNull(shop()->logo());
        Storage::disk('public')->assertMissing($current);
    }

    public function test_an_invalid_logo_is_rejected_and_replaces_nothing(): void
    {
        Storage::fake('public');
        $this->makeAdmin();

        $this->from(route('admin.settings.edit'))->put('/admin/pengaturan', $this->settingsPayload([
            'logo' => $this->fakeImage(),
        ]))->assertSessionHasErrors('logo');

        $this->assertNull(shop()->logo());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
