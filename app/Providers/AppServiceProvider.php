<?php

namespace App\Providers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use App\Observers\ActivityObserver;
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
        // Registrasi Observer untuk log aktivitas otomatis
        User::observe(ActivityObserver::class);
        Book::observe(ActivityObserver::class);
        Order::observe(ActivityObserver::class);
        Category::observe(ActivityObserver::class);
    }
}
