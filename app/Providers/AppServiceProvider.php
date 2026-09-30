<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
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
        Paginator::defaultView('vendor.pagination.mini');

        // تنسيق تاريخ بلغة الواجهة: {{ $date?->fmt() }}
        Carbon::macro('fmt', function (string $format = 'j M Y') {
            return $this->copy()->locale(app()->getLocale())->translatedFormat($format);
        });
        //
    }
}
