<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('ordering_enabled')->default(false)->after('currency');
            $table->unsignedSmallInteger('tables_count')->default(0)->after('ordering_enabled');
        });

        Schema::create('menu_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->unsignedSmallInteger('table_number');
            $table->string('status')->default('pending')->index();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 8)->default('EGP');
            $table->text('notes')->nullable();
            $table->uuid('confirmation_token')->unique();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();
            $table->index(['restaurant_id', 'created_at']);
        });

        Schema::create('menu_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_name');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_order_items');
        Schema::dropIfExists('menu_orders');

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['ordering_enabled', 'tables_count']);
        });
    }
};
