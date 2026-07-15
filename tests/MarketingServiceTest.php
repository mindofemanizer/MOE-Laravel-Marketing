<?php

namespace Moe\Marketing\Tests;

use Moe\Marketing\Models\CommissionLedger;
use Moe\Marketing\Models\Promo;
use Moe\Marketing\Services\CommissionService;
use Moe\Marketing\Services\PromoService;

class MarketingServiceTest extends TestCase
{
    private CommissionService $commissionService;
    private PromoService $promoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = new CommissionService();
        $this->promoService = new PromoService();
    }

    public function test_can_create_promo()
    {
        $promo = $this->promoService->createPromo([
            'code' => 'DISKON10',
            'type' => 'percentage',
            'value' => 10,
            'max_uses' => 100,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $this->assertInstanceOf(Promo::class, $promo);
        $this->assertEquals('DISKON10', $promo->code);
        $this->assertTrue($promo->isValid());
    }

    public function test_can_create_commission_entry()
    {
        $ledger = CommissionLedger::create([
            'user_id' => 1,
            'type' => 'referral',
            'amount' => 5000,
            'description' => 'Komisi referral',
            'reference_type' => 'order',
            'reference_id' => 1,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(CommissionLedger::class, $ledger);
        $this->assertEquals(5000, (int) $ledger->amount);
    }
}
