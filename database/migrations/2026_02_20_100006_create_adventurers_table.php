<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adventurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('email')->unique();
            $table->string('pin_code'); // hashed 4-digit
            $table->decimal('lifetime_score', 12, 2)->default(0);
            $table->unsignedInteger('number_correct_answers')->default(0);
            $table->unsignedInteger('number_wrong_answers')->default(0);
            $table->unsignedInteger('number_full_winnings')->default(0);
            $table->unsignedInteger('number_surrender_times')->default(0);
            $table->timestamps();
            $table->index('lifetime_score');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventurers');
    }
};
