# MOE-Laravel-Marketing

Marketing module for MOE ecosystem — Commission, Referral, Attribution, Promo.

## Installation

```bash
composer require moe/laravel-marketing
php artisan vendor:publish --provider="Moe\Marketing\MarketingServiceProvider" --tag="marketing-config"
php artisan vendor:publish --provider="Moe\Marketing\MarketingServiceProvider" --tag="marketing-migrations"
php artisan migrate
```

## What's Included

### Models

| Model | Table | Description |
|-------|-------|-------------|
| `CommissionLedger` | `commission_ledger` | Commission tracking (on_hold/released/reversed) |
| `MarketingAttributionLog` | `marketing_attribution_logs` | Audit log attribution |
| `Promo` | `promos` | Promo/discount codes |
| `PromoUsage` | `promo_usages` | Promo usage records |

### Services

| Service | Description |
|---------|-------------|
| `CommissionService` | Recognize, reverse, release commissions |
| `ReferralService` | Referral link, cookie, attribution |
| `PromoService` | Validate, apply, record promo usage |

### Contracts

| Contract | Description |
|----------|-------------|
| `CommissionableInterface` | Interface for commissionable items |
| `ReferralInterface` | Interface for referral tracking |
| `PromoInterface` | Interface for promo validation |

## Usage

### Commission

```php
use Moe\Marketing\Services\CommissionService;

$commissionService = app(CommissionService::class);

// Recognize commission when order is paid
$commissionService->recognize($order);

// Reverse commission when order is cancelled/refunded
$commissionService->reverse($order, 'Order cancelled');

// Release due commissions (run via cron)
$commissionService->releaseDue();
```

### Referral

```php
use Moe\Marketing\Services\ReferralService;

$referralService = app(ReferralService::class);

// Find marketing by referral slug
$marketing = $referralService->findBySlug('MARKET0005');

// Set referral cookie
$referralService->setReferralCookie('MARKET0005');

// Attribute customer
$referralService->attribute($customerId, $marketingId, 'link');
```

### Promo

```php
use Moe\Marketing\Services\PromoService;

$promoService = app(PromoService::class);

// Validate promo code
$result = $promoService->validateAndApply('DISKON10', 100000, $userId);

// Record usage
$promoService->recordUsage($promo, $orderId, $userId, $result['discount']);
```

## Config

```php
// config/marketing.php
return [
    'commission' => [
        'default_rate' => '10',
        'hold_days' => 7,
        'min_withdraw' => 50000,
    ],
];
```

## Requirements

- PHP ^8.2
- Laravel ^12.0|^13.0
- `moe/laravel-core`
- `moe/laravel-finance`
- `moe/laravel-commerce`

## License

MIT
