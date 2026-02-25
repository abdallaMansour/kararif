<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('points');
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('SAR');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_packages');
    }
};
