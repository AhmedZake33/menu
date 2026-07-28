<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('map_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('currency', 8)->default('EGP');
            $table->string('default_locale', 8)->default('ar');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('restaurant_admin')->index();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->softDeletes();
        });

        Schema::create('menu_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('page_type')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_header')->default(true);
            $table->boolean('show_restaurant_info')->default(true);
            $table->boolean('show_social_links')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['restaurant_id', 'slug']);
            $table->index(['restaurant_id', 'sort_order']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_page_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('display_style')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['menu_page_id', 'slug']);
            $table->index(['restaurant_id', 'menu_page_id', 'sort_order']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->unsignedInteger('calories')->nullable();
            $table->unsignedInteger('preparation_time')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['restaurant_id', 'category_id', 'sort_order']);
        });

        Schema::create('item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('single');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('min_choices')->nullable();
            $table->unsignedInteger('max_choices')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('item_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_option_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('additional_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('menu_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_page_id')->unique()->constrained()->cascadeOnDelete();
            foreach (['primary_color', 'secondary_color', 'background_color', 'card_background_color', 'text_color', 'heading_color', 'price_color', 'button_color', 'border_color', 'font_family', 'heading_font_family'] as $column) {
                $table->string($column)->nullable();
            }
            $table->string('layout_type')->default('grid');
            $table->string('category_layout')->default('tabs');
            $table->string('item_card_style')->default('vertical');
            $table->string('image_position')->default('top');
            $table->string('image_shape')->default('rounded');
            $table->unsignedTinyInteger('items_per_row_desktop')->default(3);
            $table->unsignedTinyInteger('items_per_row_tablet')->default(2);
            $table->unsignedTinyInteger('items_per_row_mobile')->default(1);
            $table->unsignedSmallInteger('card_border_radius')->default(12);
            $table->string('card_shadow')->nullable();
            $table->string('content_width')->nullable();
            foreach (['show_item_images', 'show_descriptions', 'show_prices', 'show_category_images', 'sticky_categories', 'enable_search', 'enable_category_filter'] as $column) {
                $table->boolean($column)->default(true);
            }
            $table->boolean('enable_dark_mode')->default(false);
            $table->timestamps();
        });

        Schema::create('menu_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->timestamp('viewed_at')->index();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->nullableMorphs('subject');
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('menu_views');
        Schema::dropIfExists('menu_themes');
        Schema::dropIfExists('item_option_values');
        Schema::dropIfExists('item_options');
        Schema::dropIfExists('items');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('menu_pages');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('restaurant_id'));
        Schema::dropIfExists('restaurants');
    }
};
