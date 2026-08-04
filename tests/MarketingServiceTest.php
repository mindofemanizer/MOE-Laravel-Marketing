<?php

use Moe\Marketing\Models\CommissionLedger;
use Moe\Marketing\Models\Promo;

it('can create promo', function () {
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

    expect($promo)->toBeInstanceOf(Promo::class);
    expect($promo->code)->toEqual('DISKON10');
    expect($promo->isValid())->toBeTrue();
});

it('can calculate percentage discount', function () {
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
    expect($discount)->toEqual(10000);
});

it('can create commission entry', function () {
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

    expect($ledger)->toBeInstanceOf(CommissionLedger::class);
    expect((int) $ledger->amount)->toEqual(5000);
    expect($ledger->status)->toEqual('on_hold');
});
