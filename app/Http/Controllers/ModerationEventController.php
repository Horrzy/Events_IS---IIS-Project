<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationEventController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled', 'all'], true)) {
            $status = 'pending';
        }

        $events = Event::query()
            ->with(['venue', 'category', 'manager'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending' => Event::where('status', 'pending')->count(),
            'approved' => Event::where('status', 'approved')->count(),
            'rejected' => Event::where('status', 'rejected')->count(),
            'cancelled' => Event::where('status', 'cancelled')->count(),
            'all' => Event::count(),
        ];

        return view('moderation.events.index', [
            'events' => $events,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function show(Event $event, EventService $eventService): View
    {
        $event->load(['venue', 'category', 'manager', 'reviewer', 'ticketTypes']);
        $collisions = $eventService->findVenueCollisions($event);

        return view('moderation.events.show', [
            'event' => $event,
            'collisions' => $collisions,
        ]);
    }

    public function approve(Request $request, Event $event): RedirectResponse
    {
        if ($event->status === 'cancelled') {
            return back()->with('error', 'Zrušenou událost nelze schválit.');
        }

        $event->status = 'approved';
        $event->reviewed_by = $request->user()->id;
        $event->reviewed_at = now();
        $event->rejection_reason = null;
        $event->save();

        return redirect()
            ->route('moderation.events.index', ['status' => 'pending'])
            ->with('success', "Událost „{$event->title}“ byla schválena.");
    }

    public function reject(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Uveďte důvod zamítnutí události.',
            'rejection_reason.min' => 'Důvod zamítnutí musí mít alespoň :min znaky.',
        ]);

        $event->status = 'rejected';
        $event->reviewed_by = $request->user()->id;
        $event->reviewed_at = now();
        $event->rejection_reason = $validated['rejection_reason'];
        $event->save();

        return redirect()
            ->route('moderation.events.index', ['status' => 'pending'])
            ->with('success', "Událost „{$event->title}“ byla zamítnuta.");
    }
}
