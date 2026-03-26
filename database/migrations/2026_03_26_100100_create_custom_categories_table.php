<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_adventurer_id')->nullable()->constrained('adventurers')->nullOnDelete();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['owner_user_id', 'owner_adventurer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_categories');
    }
};
