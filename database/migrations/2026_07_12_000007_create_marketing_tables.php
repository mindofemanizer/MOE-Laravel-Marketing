<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Commission Ledger
        Schema::create('marketing_commission_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('commerce_orders')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('commerce_order_items')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('rate', 5, 2);
            $table->string('status', 50)->default('on_hold');
            $table->timestamp('release_due_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['marketing_user_id', 'status']);
            $table->index(['order_id', 'status']);
        });

        // Marketing Attribution Log
        Schema::create('marketing_attribution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('from_marketing_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_marketing_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('source', 50)->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Promos
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->decimal('discount_value', 15, 2);
            $table->decimal('minimum_order', 15, 2)->default(0);
            $table->decimal('maximum_discount', 15, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_user')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('applies_to', 50)->nullable();
            $table->json('applicable_ids')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Promo Usages
        Schema::create('promo_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('commerce_orders')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('discount_amount', 15, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_usages');
        Schema::dropIfExists('promos');
        Schema::dropIfExists('marketing_attribution_logs');
        Schema::dropIfExists('commission_ledger');
    }
};
