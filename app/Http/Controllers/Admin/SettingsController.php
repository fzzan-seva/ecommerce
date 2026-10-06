<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Every editable setting, in form-section order.
     */
    private const STORE_KEYS = ['name', 'tagline', 'description', 'phone', 'email', 'address'];

    private const COMMERCE_KEYS = ['currency', 'currency_symbol', 'currency_decimals', 'shipping_cost'];

    private const SOCIAL_KEYS = ['whatsapp', 'instagram', 'facebook'];

    public function edit()
    {
        $settings = shop();

        return view('admin.settings.edit', [
            'settings' => $settings,
            'methods' => $settings->paymentMethods(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
            'remove_logo' => ['sometimes', 'boolean'],

            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:6'],
            'currency_decimals' => ['required', 'integer', 'min:0', 'max:4'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],

            'whatsapp' => ['nullable', 'string', 'max:30'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:100'],

            'payment_methods' => ['required', 'array', 'min:1'],
            // orders.payment_method is a VARCHAR(20) column.
            'payment_methods.*.key' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9_]+$/'],
            'payment_methods.*.label' => ['required', 'string', 'max:50'],
            'payment_methods.*.type' => ['nullable', 'string', 'max:20'],
            'payment_methods.*.account' => ['required', 'string', 'max:40'],
            'payment_methods.*.account_name' => ['nullable', 'string', 'max:60'],
            'payment_methods.*.enabled' => ['sometimes', 'boolean'],
        ], [
            'payment_methods.*.key.regex' => 'Kode metode hanya boleh berisi huruf kecil, angka dan garis bawah.',
        ]);

        // Duplicate keys would silently overwrite each other in the array.
        $keys = array_column($validated['payment_methods'], 'key');

        if (count($keys) !== count(array_unique($keys))) {
            return back()
                ->withInput()
                ->withErrors(['payment_methods' => 'Kode metode pembayaran tidak boleh duplikat.']);
        }

        $this->saveLogo($request, $validated);

        $paymentMethods = [];

        foreach ($validated['payment_methods'] as $method) {
            $paymentMethods[$method['key']] = [
                'label' => $method['label'],
                'type' => $method['type'] ?: 'bank',
                'account' => $method['account'],
                'account_name' => $method['account_name'] ?? '',
                'enabled' => (bool) ($method['enabled'] ?? false),
            ];
        }

        Setting::setMany([
            'name' => $validated['name'],
            'tagline' => $validated['tagline'] ?? '',
            'description' => $validated['description'] ?? '',
            'phone' => $validated['phone'] ?? '',
            'email' => $validated['email'] ?? '',
            'address' => $validated['address'] ?? '',
            'currency' => $validated['currency'],
            'currency_symbol' => $validated['currency_symbol'],
            'currency_decimals' => (string) $validated['currency_decimals'],
            'shipping_cost' => (string) $validated['shipping_cost'],
            'whatsapp' => $validated['whatsapp'] ?? '',
            'instagram' => $validated['instagram'] ?? '',
            'facebook' => $validated['facebook'] ?? '',
            'payment_methods' => $paymentMethods,
        ]);

        // Drop the per-request cache so the redirect renders fresh values.
        shop()->refresh();

        return redirect()->route('admin.settings.edit')->with('success', 'Pengaturan toko disimpan.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function saveLogo(Request $request, array $validated): void
    {
        $current = shop()->logo();

        if ($request->boolean('remove_logo')) {
            if ($current) {
                Storage::disk('public')->delete($current);
            }

            Setting::set('logo', '');

            return;
        }

        if ($request->hasFile('logo')) {
            if ($current) {
                Storage::disk('public')->delete($current);
            }

            Setting::set('logo', $request->file('logo')->store('logos', 'public'));
        }
    }
}
