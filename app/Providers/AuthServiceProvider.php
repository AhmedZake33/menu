<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\MenuPage;
use App\Models\MenuTheme;
use App\Models\Restaurant;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [Restaurant::class => TenantPolicy::class, MenuPage::class => TenantPolicy::class, Category::class => TenantPolicy::class, Item::class => TenantPolicy::class, MenuTheme::class => TenantPolicy::class, ItemOption::class => TenantPolicy::class];
}
