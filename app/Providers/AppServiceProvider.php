<?php

namespace App\Providers;

use App\Support\Permissions;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
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
        // مدير النظام يملك كل الصلاحيات؛ وغيره بحسب قائمة صلاحياته.
        Gate::before(fn ($user) => $user->is_admin ? true : null);
        Gate::define('companies.view', fn ($user) => $user->canViewCompanies());
        foreach (Permissions::all() as $permission) {
            Gate::define($permission, fn ($user) => in_array($permission, $user->permissions ?? [], true));
        }

        Paginator::defaultView('vendor.pagination.mini');

        // تنسيق تاريخ بلغة الواجهة: {{ $date?->fmt() }}
        Carbon::macro('fmt', function (string $format = 'j M Y') {
            return $this->copy()->locale(app()->getLocale())->translatedFormat($format);
        });
        //
    }
}
