<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Event;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function storeRating(Request $request, string $slug): RedirectResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if ($error = $this->checkEligibility($event, $user)) {
            return back()->with('error', $error);
        }

        $validated = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'stars.required' => 'Vyberte počet hvězdiček (1 až 5).',
            'stars.min' => 'Hodnocení musí být alespoň 1 hvězdička.',
            'stars.max' => 'Hodnocení může být nejvýše 5 hvězdiček.',
        ]);

        Rating::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['stars' => (int) $validated['stars']]
        );

        return redirect()
            ->route('events.show', $event->slug)
            ->with('success', 'Vaše hodnocení bylo uloženo.');
    }

    public function storeComment(Request $request, string $slug): RedirectResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if ($error = $this->checkEligibility($event, $user)) {
            return back()->with('error', $error);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'body.required' => 'Text komentáře nemůže být prázdný.',
            'body.min' => 'Komentář musí mít alespoň :min znaky.',
            'body.max' => 'Komentář může mít maximálně :max znaků.',
        ]);

        $existing = Comment::where('event_id', $event->id)->where('user_id', $user->id)->first();
        if ($existing !== null && $existing->is_hidden) {
            return back()->with('error', 'Váš komentář byl skryt moderátorem a nelze jej upravit.');
        }

        Comment::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['body' => trim($validated['body'])]
        );

        return redirect()
            ->route('events.show', $event->slug)
            ->with('success', $existing ? 'Komentář byl upraven.' : 'Komentář byl přidán.');
    }

    public function destroyComment(Request $request, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        if ($comment->user_id !== $user->id && !$user->isModerator()) {
            abort(403, 'Smazat můžete pouze vlastní komentář.');
        }

        $comment->delete();

        return back()->with('success', 'Komentář byl smazán.');
    }

    private function checkEligibility(Event $event, User $user): ?string
    {
        if ($event->status !== 'approved') {
            return 'Hodnotit a komentovat lze pouze schválené události.';
        }

        if ($event->ends_at->isFuture()) {
            return 'Hodnotit a komentovat lze až po skončení události.';
        }

        $isConfirmedParticipant = $event->registrations()
            ->where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->exists();

        if (!$isConfirmedParticipant) {
            return 'Hodnotit a komentovat mohou pouze účastníci s potvrzenou registrací.';
        }

        return null;
    }
}
