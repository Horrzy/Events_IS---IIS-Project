<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
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
        $newCapacity = isset($data['capacity']) && $data['capacity'] !== ''
            ? (int) $data['capacity']
            : null;

        $this->validateCapacities($event, $newCapacity, $tickets);

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
            $newCapacity,
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
            $model->capacity = $newCapacity;
            $model->cover_path = $newCoverPath;
            $model->payment_note = isset($data['payment_note']) && trim((string) $data['payment_note']) !== ''
                ? trim((string) $data['payment_note'])
                : null;

            if ($isNew) {
                $model->manager_id = $manager->id;
                $model->status = $submitImmediately ? 'pending' : 'draft';
                $model->submitted_at = $submitImmediately ? now() : null;
            } elseif ($model->status === 'approved') {
                // Jakákoli úprava schválené události pořadatelem ji vrací ke schválení moderátorem
                $model->status = 'pending';
                $model->submitted_at = now();
                $model->reviewed_by = null;
                $model->reviewed_at = null;
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
    private function validateCapacities(?Event $event, ?int $eventCapacity, array $tickets): void
    {
        if ($event !== null && $eventCapacity !== null) {
            $activeRegistrations = Registration::query()
                ->where('event_id', $event->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            if ($eventCapacity < $activeRegistrations) {
                throw new DomainException("Kapacitu události nelze snížit na {$eventCapacity}, protože je již registrováno {$activeRegistrations} účastníků.");
            }
        }

        foreach ($tickets as $ticketData) {
            $ticketCap = isset($ticketData['capacity']) && $ticketData['capacity'] !== ''
                ? (int) $ticketData['capacity']
                : null;
            $ticketName = trim((string) ($ticketData['name'] ?? 'Vstupenka'));

            if ($ticketCap !== null && $eventCapacity !== null && $ticketCap > $eventCapacity) {
                throw new DomainException("Kapacita vstupného „{$ticketName}“ ({$ticketCap}) nemůže převyšovat celkovou kapacitu události ({$eventCapacity}).");
            }

            $ticketId = isset($ticketData['id']) && $ticketData['id'] !== '' ? (int) $ticketData['id'] : null;
            if ($event !== null && $ticketId !== null && $ticketCap !== null) {
                $ticketRegistrations = Registration::query()
                    ->where('event_id', $event->id)
                    ->where('ticket_type_id', $ticketId)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count();

                if ($ticketCap < $ticketRegistrations) {
                    throw new DomainException("Kapacitu vstupného „{$ticketName}“ nelze snížit na {$ticketCap}, protože jej má již {$ticketRegistrations} registrovaných účastníků.");
                }
            }
        }
    }

    /**
     * @param array<int, array{id?: int|null, name: string, price: string|int|float, capacity?: int|null}> $tickets
     */
    private function syncTicketTypes(Event $event, array $tickets): void
    {
        $existing = $event->ticketTypes()->get()->keyBy('id');
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

        foreach ($existing as $ticket) {
            if (!in_array($ticket->id, $keptIds, true)) {
                $hasActiveRegs = Registration::query()
                    ->where('ticket_type_id', $ticket->id)
                    ->exists();
                if ($hasActiveRegs) {
                    throw new DomainException("Typ vstupného „{$ticket->name}“ nelze odebrat, protože jsou na něj již navázané registrace.");
                }
                $ticket->delete();
            }
        }
    }
}
