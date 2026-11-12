<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // null = the client has never been configured and gets the defaults in config/modules.php.
            $table->json('modules')->nullable();
            $table->json('settings')->nullable();
        });

        Schema::table('menu_orders', function (Blueprint $table) {
            $table->string('source', 20)->default('menu')->index();
            $table->string('order_type', 20)->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('service', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->string('payment_method', 20)->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // A sale rung up at the cashier has no customer email.
        Schema::table('menu_orders', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('menu_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashier_id');
            $table->dropIndex(['source']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['paid_at']);
            $table->dropColumn(['source', 'order_type', 'discount', 'service', 'tax', 'payment_status', 'payment_method', 'paid_at']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['modules', 'settings']);
        });
    }
};
