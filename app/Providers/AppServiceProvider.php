<?php

namespace App\Providers;

use App\View\Composers\MenuComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
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
        // Menu sidebar disiapkan oleh composer, bukan di dalam view.
        View::composer('partials.sidebar', MenuComposer::class);

        // Tampilan paginasi PANTRACK (menggantikan tampilan Tailwind bawaan Laravel).
        Paginator::defaultView('vendor.pagination.pantrack');
    }
}
