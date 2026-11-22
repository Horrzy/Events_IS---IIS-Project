<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // Register application services
    public function register(): void
    {
    }

    // Bootstrap application services and share moderation counts with views
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['layouts.app', 'moderation._nav'], function ($view): void {
            if (!Auth::check() || !Auth::user()->isModerator()) {
                return;
            }

            $pendingVenues = Venue::where('status', 'pending')->count();
            $pendingCategories = Category::where('status', 'pending')->count();
            $pendingEvents = Event::where('status', 'pending')->count();
            $total = $pendingVenues + $pendingCategories + $pendingEvents;

            $view->with([
                'pendingVenuesCount' => $pendingVenues,
                'pendingCategoriesCount' => $pendingCategories,
                'pendingEventsCount' => $pendingEvents,
                'pendingModerationCount' => $total,
                'pendingModerationBadge' => $total > 9 ? '9+' : (string) $total,
            ]);
        });
    }
}
