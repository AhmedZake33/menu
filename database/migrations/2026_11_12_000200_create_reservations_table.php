<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->unsignedSmallInteger('guests');
            $table->dateTime('reserved_at');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
            $table->index(['restaurant_id', 'reserved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
