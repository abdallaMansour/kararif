<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_subcategory_stage_tails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained()->cascadeOnDelete();
            $table->string('creator_owner_type', 16); // user | adventurer
            $table->unsignedBigInteger('creator_owner_id');
            $table->string('last_round_stage_type', 32);
            $table->timestamps();

            $table->unique(['subcategory_id', 'creator_owner_type', 'creator_owner_id'], 'creator_subcat_tail_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_subcategory_stage_tails');
    }
};
