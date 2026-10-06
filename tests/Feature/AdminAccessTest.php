<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_a_normal_customer_cannot_open_any_admin_route(): void
    {
        $this->makeCustomer();

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/produk')->assertForbidden();
        $this->get('/admin/produk/create')->assertForbidden();
        $this->post('/admin/produk', [])->assertForbidden();
        $this->get('/admin/kategori')->assertForbidden();
        $this->post('/admin/kategori', ['name' => 'Nope'])->assertForbidden();
        $this->get('/admin/pengaturan')->assertForbidden();
        $this->put('/admin/pengaturan', ['name' => 'Nope'])->assertForbidden();
        $this->get('/admin/pesanan')->assertForbidden();
        $this->get('/admin/pengguna')->assertForbidden();
    }

    public function test_an_admin_can_open_the_admin_panel(): void
    {
        $this->makeAdmin();

        $this->get('/admin')->assertOk();
        $this->get('/admin/produk')->assertOk();
        $this->get('/admin/kategori')->assertOk();
        $this->get('/admin/pengaturan')->assertOk();
        $this->get('/admin/pesanan')->assertOk();
        $this->get('/admin/pengguna')->assertOk();
    }

    public function test_admin_status_endpoint_rejects_normal_customers(): void
    {
        $customer = $this->makeCustomer();
        $admin = $this->makeAdmin(false);

        $order = $customer->orders()->create([
            'order_number' => 'ORD-TEST1',
            'recipient_name' => 'A',
            'phone' => '1',
            'shipping_address' => 'B',
            'subtotal' => 1000,
            'shipping_cost' => 0,
            'total' => 1000,
            'status' => 'pending',
            'payment_method' => 'bank_transfer',
        ]);

        $this->actingAs($customer)
            ->patch("/admin/pesanan/{$order->id}/status", ['status' => 'cancelled'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch("/admin/pesanan/{$order->id}/status", ['status' => 'paid'])
            ->assertRedirect();

        $this->assertSame('paid', $order->fresh()->status);
    }
}
