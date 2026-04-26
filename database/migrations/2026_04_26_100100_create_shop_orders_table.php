<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('status', 30)->default('pending_payment');

            $table->string('customer_full_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email');

            $table->string('delivery_emirate');
            $table->string('delivery_area');
            $table->text('delivery_detail');

            $table->decimal('subtotal_aed', 10, 2);
            $table->decimal('shipping_fee_aed', 10, 2)->default(30);
            $table->decimal('total_aed', 10, 2);

            $table->string('gateway_name', 30)->default('ziina');
            $table->string('gateway_payment_intent_id')->nullable()->index();
            $table->string('gateway_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
    }
};
