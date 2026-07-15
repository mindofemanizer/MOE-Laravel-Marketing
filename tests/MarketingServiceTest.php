<?php

namespace Moe\Marketing\Tests;

use Moe\Marketing\Models\CommissionLedger;
use Moe\Marketing\Models\Promo;

class MarketingServiceTest extends TestCase
{
    public function test_can_create_promo()
    {
        $promo = Promo::create([
            'code' => 'DISKON10',
            'name' => 'Diskon 10%',
            'type' => 'percentage',
            'discount_value' => 10,
            'minimum_order' => 0,
            'usage_limit' => 100,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Promo::class, $promo);
        $this->assertEquals('DISKON10', $promo->code);
        $this->assertTrue($promo->isValid());
    }

    public function test_can_calculate_percentage_discount()
    {
        $promo = Promo::create([
            'code' => 'DISKON10',
            'name' => 'Diskon 10%',
            'type' => 'percentage',
            'discount_value' => 10,
            'minimum_order' => 0,
            'usage_limit' => 100,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'is_active' => true,
        ]);

        $discount = $promo->getDiscount(100000);
        $this->assertEquals(10000, $discount);
    }

    public function test_can_create_commission_entry()
    {
        $userClass = config('marketing.models.user');
        $user = $userClass::create(['name' => 'Test', 'email' => 'test@test.com', 'password' => bcrypt('secret')]);

        $ledger = CommissionLedger::create([
            'marketing_user_id' => $user->id,
            'customer_user_id' => $user->id,
            'order_id' => 1,
            'order_item_id' => 1,
            'amount' => 5000,
            'rate' => 10,
            'status' => 'on_hold',
        ]);

        $this->assertInstanceOf(CommissionLedger::class, $ledger);
        $this->assertEquals(5000, (int) $ledger->amount);
        $this->assertEquals('on_hold', $ledger->status);
    }
}
