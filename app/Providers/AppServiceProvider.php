<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use App\Models\User;
use App\Observers\UserObserver;

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
        // Force set timezone to Asia/Jakarta
        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
        
        if (class_exists(User::class) && class_exists(UserObserver::class)) {
            User::observe(UserObserver::class);
        }

        // Standardize pagination rendering across all views
        Paginator::defaultView('pagination.custom');
        Paginator::defaultSimpleView('pagination.custom');

        // Standardized pagination size resolver:
        // reads ?per_page (allowed 25/50/100, max 100), falls back to $default (25),
        // and keeps per_page in generated links so the selection persists.
        $resolvePerPage = function (int $default = 25): int {
            $allowed = [25, 50, 100];
            $requested = (int) request()->input('per_page', 0);
            if ($requested > 0) {
                if ($requested > 100) {
                    $requested = 100;
                }
                return in_array($requested, $allowed, true) ? $requested : $default;
            }
            return $default;
        };

        $paginateStd = function ($default = 25, $columns = ['*'], $pageName = 'page', $page = null) use ($resolvePerPage) {
            /** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $this */
            $perPage = $resolvePerPage((int) $default);
            return $this->paginate($perPage, $columns, $pageName, $page)
                        ->appends(request()->except($pageName));
        };

        EloquentBuilder::macro('paginateStd', $paginateStd);
        QueryBuilder::macro('paginateStd', $paginateStd);
    }
}
