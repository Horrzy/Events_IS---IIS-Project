<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $upcomingEvents = Event::query()
            ->with(['venue', 'category', 'ticketTypes'])
            ->where('status', 'approved')
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(6)
            ->get();

        return view('home', [
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
