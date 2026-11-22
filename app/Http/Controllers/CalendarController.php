<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\IcsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $items = $this->collectUserEvents($user->id);

        $upcoming = $items->filter(fn (array $item) => $item['event']->ends_at->isFuture())
            ->sortBy(fn (array $item) => $item['event']->starts_at->timestamp)
            ->values();

        $past = $items->filter(fn (array $item) => $item['event']->ends_at->isPast())
            ->sortByDesc(fn (array $item) => $item['event']->starts_at->timestamp)
            ->values();

        return view('calendar.index', [
            'upcoming' => $upcoming,
            'past' => $past,
        ]);
    }

    public function export(Request $request, IcsService $icsService): Response
    {
        $user = $request->user();
        $events = $this->collectUserEvents($user->id)
            ->pluck('event')
            ->sortBy(fn (Event $e) => $e->starts_at->timestamp)
            ->values();

        $host = parse_url((string) config('app.url', 'http://iis.local'), PHP_URL_HOST) ?: 'iis.local';
        $content = $icsService->generate($events, $host);

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="muj-kalendar.ics"',
        ]);
    }

    // Merge approved events the user registered for with approved events they manage
    private function collectUserEvents(int $userId): Collection
    {
        $byEventId = [];

        $registeredEvents = Event::query()
            ->with(['venue', 'category', 'registrations' => fn ($q) => $q->where('user_id', $userId)->whereIn('status', ['pending', 'confirmed'])])
            ->where('status', 'approved')
            ->whereHas('registrations', fn ($q) => $q->where('user_id', $userId)->whereIn('status', ['pending', 'confirmed']))
            ->get();

        foreach ($registeredEvents as $event) {
            $reg = $event->registrations->first();
            $byEventId[$event->id] = [
                'event' => $event,
                'relation' => 'Účastník',
                'registration_status' => $reg?->status,
            ];
        }

        $managedEvents = Event::query()
            ->with(['venue', 'category'])
            ->where('manager_id', $userId)
            ->where('status', 'approved')
            ->get();

        foreach ($managedEvents as $event) {
            if (isset($byEventId[$event->id])) {
                $byEventId[$event->id]['relation'] = 'Pořadatel i účastník';
            } else {
                $byEventId[$event->id] = [
                    'event' => $event,
                    'relation' => 'Pořadatel',
                    'registration_status' => null,
                ];
            }
        }

        return collect(array_values($byEventId));
    }
}
