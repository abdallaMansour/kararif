<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_displays', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 255)->index();
            $table->string('code', 6)->unique();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null');
            $table->string('status', 20)->default('waiting'); // waiting, linked, expired
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_displays');
    }
};
