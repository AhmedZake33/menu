<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('table_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Orders placed without a table cannot satisfy NOT NULL, so this only rolls back
        // cleanly while none exist. It is left to fail loudly rather than delete orders.
        Schema::table('menu_orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('table_number')->nullable(false)->change();
        });
    }
};
