<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Runtime access to the store's business configuration.
 *
 * Resolution order for every key:
 *
 *   1. the `settings` table (edited from Admin > Store Settings)
 *   2. config/shop.php (fed by the SHOP_* variables in .env)
 *
 * Values are loaded once per request, so calling price()/shop()->... in a
 * loop does not add queries.
 */
class Shop
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $overrides = null;

    /**
     * Read a setting (dot notation supported) with a config fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $overrides = $this->overrides();

        if (Arr::has($overrides, $key)) {
            return Arr::get($overrides, $key);
        }

        return config('shop.'.$key, $default);
    }

    public function name(): string
    {
        return (string) $this->get('name', 'Laravel E-Commerce');
    }

    public function tagline(): string
    {
        return (string) $this->get('tagline', '');
    }

    public function description(): string
    {
        return (string) $this->get('description', '');
    }

    public function logo(): ?string
    {
        $logo = (string) $this->get('logo', '');

        return $logo !== '' ? $logo : null;
    }

    public function phone(): ?string
    {
        return $this->filled('phone');
    }

    public function email(): ?string
    {
        return $this->filled('email');
    }

    public function address(): ?string
    {
        return $this->filled('address');
    }

    public function whatsapp(): ?string
    {
        return $this->filled('whatsapp');
    }

    /**
     * wa.me accepts digits only, in international format.
     */
    public function whatsappUrl(): ?string
    {
        $whatsapp = $this->whatsapp();

        if ($whatsapp === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $whatsapp);

        return $digits ? 'https://wa.me/'.$digits : null;
    }

    public function instagram(): ?string
    {
        return $this->filled('instagram');
    }

    public function instagramUrl(): ?string
    {
        $handle = $this->instagram();

        if ($handle === null) {
            return null;
        }

        return str_starts_with($handle, 'http')
            ? $handle
            : 'https://instagram.com/'.ltrim($handle, '@');
    }

    public function facebook(): ?string
    {
        return $this->filled('facebook');
    }

    public function facebookUrl(): ?string
    {
        $value = $this->facebook();

        if ($value === null) {
            return null;
        }

        return str_starts_with($value, 'http')
            ? $value
            : 'https://facebook.com/'.ltrim($value, '/');
    }

    public function currency(): string
    {
        return (string) $this->get('currency', 'IDR');
    }

    public function currencySymbol(): string
    {
        return (string) $this->get('currency_symbol', 'Rp');
    }

    public function currencyDecimals(): int
    {
        return max(0, (int) $this->get('currency_decimals', 0));
    }

    public function shippingCost(): float
    {
        return max(0, (float) $this->get('shipping_cost', 0));
    }

    public function orderPrefix(): string
    {
        $prefix = trim((string) $this->get('order_prefix', 'ORD'));

        return $prefix !== '' ? $prefix : 'ORD';
    }

    /**
     * All configured payment methods, keyed by their identifier.
     *
     * @return array<string, array{label: string, type: string, account: string, account_name: string, enabled: bool}>
     */
    public function paymentMethods(): array
    {
        $methods = $this->get('payment_methods', []);

        if (! is_array($methods)) {
            return [];
        }

        $normalized = [];

        foreach ($methods as $key => $method) {
            if (! is_array($method)) {
                continue;
            }

            $normalized[(string) $key] = [
                'label' => (string) ($method['label'] ?? ucfirst(str_replace('_', ' ', (string) $key))),
                'type' => (string) ($method['type'] ?? 'bank'),
                'account' => (string) ($method['account'] ?? ''),
                'account_name' => (string) ($method['account_name'] ?? ''),
                'enabled' => (bool) ($method['enabled'] ?? true),
            ];
        }

        return $normalized;
    }

    /**
     * Only the methods a customer may pick at checkout.
     *
     * @return array<string, array{label: string, type: string, account: string, account_name: string, enabled: bool}>
     */
    public function enabledPaymentMethods(): array
    {
        return array_filter($this->paymentMethods(), fn (array $method) => $method['enabled']);
    }

    /**
     * The default method pre-selected on the checkout form.
     */
    public function defaultPaymentMethod(): ?string
    {
        $enabled = $this->enabledPaymentMethods();

        return $enabled === [] ? null : (string) array_key_first($enabled);
    }

    public function paymentMethod(?string $key): ?array
    {
        if ($key === null || $key === '') {
            return null;
        }

        return $this->paymentMethods()[$key] ?? null;
    }

    /**
     * The one formatter used for every price in the application.
     */
    public function formatPrice(float|int|string $amount): string
    {
        $decimals = $this->currencyDecimals();

        $formatted = $decimals > 0
            ? number_format((float) $amount, $decimals, '.', ',')
            : number_format((float) $amount, 0, '.', ',');

        $symbol = $this->currencySymbol();

        return $symbol === '' ? $formatted : $symbol.' '.$formatted;
    }

    /**
     * Drop the per-request cache (after saving settings, or between tests).
     */
    public function refresh(): void
    {
        $this->overrides = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function overrides(): array
    {
        if ($this->overrides !== null) {
            return $this->overrides;
        }

        $this->overrides = [];

        try {
            if (! Schema::hasTable('settings')) {
                return $this->overrides;
            }

            foreach (Setting::query()->get() as $setting) {
                $this->overrides[$setting->key] = $this->cast($setting->key, $setting->value);
            }
        } catch (Throwable $e) {
            // No database yet (fresh install before `migrate`) — config only.
            $this->overrides = [];
        }

        return $this->overrides;
    }

    protected function cast(string $key, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($key === 'payment_methods') {
            return json_decode($value, true) ?? [];
        }

        if ($key === 'shipping_cost') {
            return (float) $value;
        }

        if ($key === 'currency_decimals') {
            return (int) $value;
        }

        return $value;
    }

    protected function filled(string $key): ?string
    {
        $value = trim((string) $this->get($key, ''));

        return $value === '' ? null : $value;
    }
}
