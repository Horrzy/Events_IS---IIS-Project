<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $registrations = Registration::query()
            ->with(['event.venue', 'ticketType'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('registrations.index', [
            'registrations' => $registrations,
        ]);
    }

    public function store(Request $request, string $slug, RegistrationService $service): RedirectResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'ticket_type_id' => ['required', 'integer'],
        ], [
            'ticket_type_id.required' => 'Vyberte typ vstupného.',
        ]);

        try {
            $registration = $service->register($request->user(), $event, (int) $validated['ticket_type_id']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $registration->status === 'confirmed'
            ? 'Registrace byla potvrzena a událost přidána do vašeho kalendáře.'
            : 'Registrace byla vytvořena a čeká na potvrzení úhrady správcem události.';

        return redirect()
            ->route('events.show', $event->slug)
            ->with('success', $message);
    }

    public function cancel(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        $registration->load('event');

        try {
            $service->cancelByUser($registration, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Registrace byla zrušena.');
    }

    public function claimPayment(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        try {
            $service->claimPayment($registration, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Oznámení o úhradě bylo uloženo. Správce události platbu brzy potvrdí.');
    }

    public function participants(Request $request, Event $event): View
    {
        $this->authorizeEventManager($request, $event);

        $registrations = Registration::query()
            ->with(['user', 'ticketType', 'confirmer'])
            ->where('event_id', $event->id)
            ->orderByRaw("FIELD(status, 'pending', 'confirmed', 'cancelled')")
            ->orderByDesc('created_at')
            ->get();

        return view('registrations.participants', [
            'event' => $event->load('venue'),
            'registrations' => $registrations,
        ]);
    }

    public function confirmPayment(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        $registration->load('event');
        $this->authorizeEventManager($request, $registration->event);

        try {
            $service->confirmPayment($registration, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Úhrada byla potvrzena a účastník je schválen.');
    }

    public function rejectPayment(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        $registration->load('event');
        $this->authorizeEventManager($request, $registration->event);

        try {
            $service->rejectPayment($registration);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Registrace byla odmítnuta a místo bylo uvolněno.');
    }

    private function authorizeEventManager(Request $request, Event $event): void
    {
        $user = $request->user();
        if ($user === null || ($event->manager_id !== $user->id && !$user->isAdmin())) {
            abort(403, 'Seznam účastníků může spravovat pouze správce události nebo administrátor.');
        }
    }
}
