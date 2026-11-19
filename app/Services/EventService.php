<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventService
{
    public const DISPLAY_TIMEZONE = 'Europe/Prague';

    public function __construct(
        private readonly ImageUploadService $uploader
    ) {
    }

    public function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'udalost';
        }

        $slug = $base;
        $counter = 2;

        while (
            Event::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Uloží novou nebo upravenou událost včetně typů vstupenek v jedné databázové transakci.
     *
     * @param array<string, mixed> $data
     * @param array<int, array{id?: int|null, name: string, price: string|int|float, capacity?: int|null}> $tickets
     */
    public function saveEvent(
        ?Event $event,
        User $manager,
        array $data,
        array $tickets,
        ?UploadedFile $coverFile = null,
        bool $removeCover = false,
        bool $submitImmediately = false
    ): Event {
        $startsAtUtc = Carbon::parse((string) $data['starts_at'], self::DISPLAY_TIMEZONE)->utc();
        $endsAtUtc = Carbon::parse((string) $data['ends_at'], self::DISPLAY_TIMEZONE)->utc();

        $oldCoverToDelete = null;
        $newCoverPath = $event?->cover_path;

        if ($removeCover && $newCoverPath !== null) {
            $oldCoverToDelete = $newCoverPath;
            $newCoverPath = null;
        }

        if ($coverFile !== null) {
            if ($event?->cover_path !== null) {
                $oldCoverToDelete = $event->cover_path;
            }
            $newCoverPath = $this->uploader->store($coverFile, 'events');
        }

        $savedEvent = DB::transaction(function () use (
            $event,
            $manager,
            $data,
            $tickets,
            $startsAtUtc,
            $endsAtUtc,
            $newCoverPath,
            $submitImmediately
        ): Event {
            $isNew = $event === null;
            $model = $event ?? new Event();

            $model->title = (string) $data['title'];
            $model->slug = $isNew
                ? $this->generateUniqueSlug($model->title)
                : $this->generateUniqueSlug($model->title, $model->id);
            $model->description = (string) $data['description'];
            $model->starts_at = $startsAtUtc;
            $model->ends_at = $endsAtUtc;
            $model->venue_id = (int) $data['venue_id'];
            $model->category_id = (int) $data['category_id'];
            $model->capacity = isset($data['capacity']) && $data['capacity'] !== ''
                ? (int) $data['capacity']
                : null;
            $model->cover_path = $newCoverPath;
            $model->payment_note = isset($data['payment_note']) && trim((string) $data['payment_note']) !== ''
                ? trim((string) $data['payment_note'])
                : null;

            if ($isNew) {
                $model->manager_id = $manager->id;
                $model->status = $submitImmediately ? 'pending' : 'draft';
                $model->submitted_at = $submitImmediately ? now() : null;
            } elseif ($model->status === 'rejected') {
                $model->status = $submitImmediately ? 'pending' : 'draft';
                $model->submitted_at = $submitImmediately ? now() : null;
                $model->rejection_reason = null;
            } elseif ($model->status === 'draft' && $submitImmediately) {
                $model->status = 'pending';
                $model->submitted_at = now();
            }

            $model->save();

            $this->syncTicketTypes($model, $tickets);

            return $model;
        });

        if ($oldCoverToDelete !== null && $oldCoverToDelete !== $savedEvent->cover_path) {
            $this->uploader->delete($oldCoverToDelete);
        }

        return $savedEvent;
    }

    public function submitForApproval(Event $event): void
    {
        $event->status = 'pending';
        $event->submitted_at = now();
        $event->rejection_reason = null;
        $event->save();
    }

    public function cancel(Event $event, string $reason): void
    {
        $event->status = 'cancelled';
        $event->cancelled_reason = trim($reason);
        $event->save();
    }

    /**
     * Najde jiné schválené nebo čekající události ve stejném místě s překrývajícím se časem.
     *
     * @return Collection<int, Event>
     */
    public function findVenueCollisions(Event $event): Collection
    {
        return Event::query()
            ->where('id', '!=', $event->id)
            ->where('venue_id', $event->venue_id)
            ->whereIn('status', ['approved', 'pending'])
            ->where('starts_at', '<', $event->ends_at)
            ->where('ends_at', '>', $event->starts_at)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @param array<int, array{id?: int|null, name: string, price: string|int|float, capacity?: int|null}> $tickets
     */
    private function syncTicketTypes(Event $event, array $tickets): void
    {
        $existing = $event->ticketTypes()->withCount('registrations')->get()->keyBy('id');
        $keptIds = [];

        foreach ($tickets as $ticketData) {
            $ticketId = isset($ticketData['id']) && $ticketData['id'] !== '' ? (int) $ticketData['id'] : null;

            if ($ticketId !== null && $existing->has($ticketId)) {
                /** @var TicketType $ticket */
                $ticket = $existing->get($ticketId);
                $ticket->update([
                    'name' => trim((string) $ticketData['name']),
                    'price' => round((float) $ticketData['price'], 2),
                    'capacity' => isset($ticketData['capacity']) && $ticketData['capacity'] !== ''
                        ? (int) $ticketData['capacity']
                        : null,
                ]);
                $keptIds[] = $ticket->id;
            } else {
                $created = $event->ticketTypes()->create([
                    'name' => trim((string) $ticketData['name']),
                    'price' => round((float) $ticketData['price'], 2),
                    'capacity' => isset($ticketData['capacity']) && $ticketData['capacity'] !== ''
                        ? (int) $ticketData['capacity']
                        : null,
                ]);
                $keptIds[] = $created->id;
            }
        }

        // Smažeme jen ty odebrané typy vstupenek, na které ještě nikdo není registrovaný
        foreach ($existing as $ticket) {
            if (!in_array($ticket->id, $keptIds, true) && $ticket->registrations_count === 0) {
                $ticket->delete();
            }
        }
    }
}
