<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // "digital" = the menu built in the dashboard (what every existing client has);
            // "images" = the client uploads pictures of their own menu instead.
            $table->string('menu_mode', 20)->default('digital')->index();
        });

        Schema::create('menu_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['restaurant_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_images');

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['menu_mode']);
            $table->dropColumn('menu_mode');
        });
    }
};
