<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use App\Services\CategoryService;
use App\Services\EventService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request, CategoryService $categoryService): View
    {
        $search = trim((string) $request->query('q', ''));
        $categoryId = (string) $request->query('category_id', '');
        $venueId = (string) $request->query('venue_id', '');
        $when = (string) $request->query('when', 'upcoming');
        $price = (string) $request->query('price', 'all');

        if (!in_array($when, ['upcoming', 'past', 'all'], true)) {
            $when = 'upcoming';
        }
        if (!in_array($price, ['all', 'free', 'paid'], true)) {
            $price = 'all';
        }

        $query = Event::query()
            ->with(['venue', 'category', 'ticketTypes'])
            ->where('status', 'approved');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($categoryId !== '' && ctype_digit($categoryId)) {
            $branchIds = $categoryService->getDescendantIds((int) $categoryId);
            $query->whereIn('category_id', $branchIds);
        }

        if ($venueId !== '' && ctype_digit($venueId)) {
            $query->where('venue_id', (int) $venueId);
        }

        if ($when === 'upcoming') {
            $query->where('ends_at', '>=', now())->orderBy('starts_at');
        } elseif ($when === 'past') {
            $query->where('ends_at', '<', now())->orderByDesc('starts_at');
        } else {
            $query->orderByDesc('starts_at');
        }

        if ($price === 'free') {
            $query->whereDoesntHave('ticketTypes', fn ($q) => $q->where('price', '>', 0));
        } elseif ($price === 'paid') {
            $query->whereHas('ticketTypes', fn ($q) => $q->where('price', '>', 0));
        }

        $events = $query->paginate(12)->withQueryString();

        return view('events.index', [
            'events' => $events,
            'categoryTree' => $categoryService->getApprovedTree(),
            'venues' => Venue::where('status', 'approved')->orderBy('city')->orderBy('name')->get(),
            'filters' => [
                'q' => $search,
                'category_id' => $categoryId,
                'venue_id' => $venueId,
                'when' => $when,
                'price' => $price,
            ],
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $event = Event::query()
            ->with(['venue', 'category.parent', 'manager', 'ticketTypes'])
            ->where('slug', $slug)
            ->firstOrFail();

        $user = $request->user();
        $canViewUnapproved = $user !== null
            && ($user->isModerator() || $event->manager_id === $user->id);

        if ($event->status !== 'approved' && !$canViewUnapproved) {
            abort(404);
        }

        $occupiedCount = $event->registrations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $ticketOccupiedCounts = $event->registrations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->selectRaw('ticket_type_id, COUNT(*) as cnt')
            ->groupBy('ticket_type_id')
            ->pluck('cnt', 'ticket_type_id');

        $userRegistration = $user !== null
            ? $event->registrations()
                ->with('ticketType')
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->first()
            : null;

        $googleCalendarUrl = $this->buildGoogleCalendarUrl($event);

        $ratings = $event->ratings()->with('user')->orderByDesc('updated_at')->get();
        $ratingsCount = $ratings->count();
        $averageRating = $ratingsCount > 0 ? round((float) $ratings->avg('stars'), 1) : null;
        $ratingsByUser = $ratings->keyBy('user_id');
        $userRating = $user !== null ? $ratingsByUser->get($user->id) : null;
        $userComment = $user !== null
            ? $event->comments()->where('user_id', $user->id)->first()
            : null;

        $canGiveFeedback = $user !== null
            && $event->status === 'approved'
            && $event->ends_at->isPast()
            && $userRegistration !== null
            && $userRegistration->status === 'confirmed';

        $commentsQuery = $event->comments()->with('user')->orderByDesc('created_at');
        if ($user === null || !$user->isModerator()) {
            $commentsQuery->where('is_hidden', false);
        }
        $comments = $commentsQuery->get();

        $allCommentUserIds = $event->comments()->pluck('user_id')->all();
        $ratingsOnly = $ratings->filter(
            fn ($rating) => !in_array($rating->user_id, $allCommentUserIds, true)
        );

        return view('events.show', [
            'event' => $event,
            'occupiedCount' => $occupiedCount,
            'ticketOccupiedCounts' => $ticketOccupiedCounts,
            'userRegistration' => $userRegistration,
            'googleCalendarUrl' => $googleCalendarUrl,
            'ratingsCount' => $ratingsCount,
            'averageRating' => $averageRating,
            'ratingsByUser' => $ratingsByUser,
            'ratingsOnly' => $ratingsOnly,
            'userRating' => $userRating,
            'userComment' => $userComment,
            'canGiveFeedback' => $canGiveFeedback,
            'comments' => $comments,
        ]);
    }

    public function mine(Request $request): View
    {
        $events = Event::query()
            ->with(['venue', 'category', 'ticketTypes'])
            ->withCount([
                'registrations as active_registrations_count' => fn ($q) => $q->whereIn('status', ['pending', 'confirmed']),
            ])
            ->where('manager_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('events.mine', [
            'events' => $events,
        ]);
    }

    public function create(CategoryService $categoryService): View
    {
        return view('events.form', [
            'event' => null,
            'venues' => Venue::where('status', 'approved')->orderBy('city')->orderBy('name')->get(),
            'categoryTree' => $categoryService->getApprovedTree(),
            'defaultStart' => now(EventService::DISPLAY_TIMEZONE)->addDay()->setTime(18, 0)->format('Y-m-d\TH:i'),
            'defaultEnd' => now(EventService::DISPLAY_TIMEZONE)->addDay()->setTime(20, 0)->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(Request $request, EventService $eventService): RedirectResponse
    {
        $validated = $this->validateEvent($request);
        $submitImmediately = $request->input('action') === 'submit';

        try {
            $event = $eventService->saveEvent(
                null,
                $request->user(),
                $validated,
                $validated['tickets'],
                $request->file('cover'),
                false,
                $submitImmediately
            );
        } catch (DomainException $e) {
            return back()->withErrors(['capacity' => $e->getMessage()])->withInput();
        }

        $message = $submitImmediately
            ? "Událost „{$event->title}“ byla vytvořena a odeslána ke schválení moderátorem."
            : "Koncept události „{$event->title}“ byl uložen.";

        return redirect()
            ->route('events.mine')
            ->with('success', $message);
    }

    public function edit(Request $request, Event $event, CategoryService $categoryService): View
    {
        $this->authorizeManage($request, $event);

        if ($event->status === 'cancelled') {
            abort(403, 'Zrušenou událost již nelze upravovat.');
        }

        $event->load('ticketTypes');

        return view('events.form', [
            'event' => $event,
            'venues' => Venue::where('status', 'approved')->orderBy('city')->orderBy('name')->get(),
            'categoryTree' => $categoryService->getApprovedTree(),
            'defaultStart' => $event->starts_at->copy()->setTimezone(EventService::DISPLAY_TIMEZONE)->format('Y-m-d\TH:i'),
            'defaultEnd' => $event->ends_at->copy()->setTimezone(EventService::DISPLAY_TIMEZONE)->format('Y-m-d\TH:i'),
        ]);
    }

    public function update(Request $request, Event $event, EventService $eventService): RedirectResponse
    {
        $this->authorizeManage($request, $event);

        if ($event->status === 'cancelled') {
            return back()->with('error', 'Zrušenou událost již nelze upravovat.');
        }

        $validated = $this->validateEvent($request);
        $submitImmediately = $request->input('action') === 'submit';
        $wasApproved = $event->status === 'approved';

        try {
            $eventService->saveEvent(
                $event,
                $request->user(),
                $validated,
                $validated['tickets'],
                $request->file('cover'),
                $request->boolean('remove_cover'),
                $submitImmediately
            );
        } catch (DomainException $e) {
            return back()->withErrors(['capacity' => $e->getMessage()])->withInput();
        }

        $message = $wasApproved
            ? "Událost „{$event->title}“ byla upravena a znovu odeslána ke schválení moderátorem."
            : "Událost „{$event->title}“ byla aktualizována.";

        return redirect()
            ->route('events.mine')
            ->with('success', $message);
    }

    public function submit(Request $request, Event $event, EventService $eventService): RedirectResponse
    {
        $this->authorizeManage($request, $event);

        if (!in_array($event->status, ['draft', 'rejected'], true)) {
            return back()->with('error', 'Ke schválení lze odeslat jen koncept nebo zamítnutou událost.');
        }

        $eventService->submitForApproval($event);

        return back()->with('success', "Událost „{$event->title}“ byla odeslána ke schválení moderátorem.");
    }

    public function cancel(Request $request, Event $event, EventService $eventService): RedirectResponse
    {
        $this->authorizeManage($request, $event);

        if ($event->status === 'cancelled') {
            return back()->with('error', 'Událost je již zrušená.');
        }

        $validated = $request->validate([
            'cancelled_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'cancelled_reason.required' => 'Uveďte důvod zrušení události.',
            'cancelled_reason.min' => 'Důvod zrušení musí mít alespoň :min znaky.',
        ]);

        $eventService->cancel($event, $validated['cancelled_reason']);

        return back()->with('success', "Událost „{$event->title}“ byla zrušena.");
    }

    private function authorizeManage(Request $request, Event $event): void
    {
        $user = $request->user();
        if ($user === null || ($event->manager_id !== $user->id && !$user->isAdmin())) {
            abort(403, 'Tuto událost může spravovat pouze její pořadatel nebo administrátor.');
        }
    }

    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'venue_id' => [
                'required',
                'integer',
                Rule::exists('venues', 'id')->where('status', 'approved'),
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('status', 'approved'),
            ],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
            'cover' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_cover' => ['nullable', 'boolean'],
            'tickets' => ['required', 'array', 'min:1'],
            'tickets.*.id' => ['nullable', 'integer'],
            'tickets.*.name' => ['required', 'string', 'max:100'],
            'tickets.*.price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'tickets.*.capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'title.required' => 'Název události je povinný.',
            'description.required' => 'Popis události je povinný.',
            'starts_at.required' => 'Začátek události je povinný.',
            'ends_at.required' => 'Konec události je povinný.',
            'ends_at.after' => 'Konec události musí být až po jejím začátku.',
            'venue_id.required' => 'Vyberte místo konání.',
            'venue_id.exists' => 'Vybrané místo musí být schválené.',
            'category_id.required' => 'Vyberte kategorii události.',
            'category_id.exists' => 'Vybraná kategorie musí být schválená.',
            'capacity.min' => 'Kapacita musí být alespoň 1 (nebo nechte prázdné pro neomezenou).',
            'cover.image' => 'Titulní obrázek musí být platný obrázek.',
            'cover.mimes' => 'Podporované formáty obrázku jsou JPG, PNG a WebP.',
            'cover.max' => 'Obrázek může mít maximálně 2 MB.',
            'tickets.required' => 'Událost musí mít alespoň jeden typ vstupného.',
            'tickets.*.name.required' => 'Název typu vstupného je povinný.',
            'tickets.*.price.required' => 'Cena vstupného je povinná (0 pro vstup zdarma).',
            'tickets.*.price.min' => 'Cena nemůže být záporná.',
        ]);
    }

    private function buildGoogleCalendarUrl(Event $event): string
    {
        $dates = $event->starts_at->copy()->utc()->format('Ymd\THis\Z')
            . '/'
            . $event->ends_at->copy()->utc()->format('Ymd\THis\Z');

        $location = $event->venue
            ? trim($event->venue->name . ', ' . $event->venue->street . ', ' . $event->venue->city)
            : '';

        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action' => 'TEMPLATE',
            'text' => $event->title,
            'dates' => $dates,
            'details' => $event->description,
            'location' => $location,
        ]);
    }
}
