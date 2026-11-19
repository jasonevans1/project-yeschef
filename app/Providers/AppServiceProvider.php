<?php

namespace App\Providers;

use App\Listeners\ResolvePendingShares;
use App\Models\GroceryItem;
use App\Observers\GroceryItemObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        GroceryItem::observe(GroceryItemObserver::class);

        Event::listen(Registered::class, ResolvePendingShares::class);
    }
}
