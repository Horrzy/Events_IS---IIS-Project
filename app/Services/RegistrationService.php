<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function register(User $user, Event $event, int $ticketTypeId): Registration
    {
        return DB::transaction(function () use ($user, $event, $ticketTypeId): Registration {
            /** @var Event $lockedEvent */
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->status !== 'approved') {
                throw new DomainException('Na tuto událost se momentálně nelze registrovat.');
            }

            if ($lockedEvent->ends_at->isPast()) {
                throw new DomainException('Událost již skončila, registrace není možná.');
            }

            $alreadyRegistered = Registration::query()
                ->where('event_id', $lockedEvent->id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($alreadyRegistered) {
                throw new DomainException('Na tuto událost již máte aktivní registraci.');
            }

            /** @var TicketType|null $ticketType */
            $ticketType = TicketType::query()
                ->where('id', $ticketTypeId)
                ->where('event_id', $lockedEvent->id)
                ->lockForUpdate()
                ->first();

            if ($ticketType === null) {
                throw new DomainException('Vybraný typ vstupného neexistuje.');
            }

            if ($lockedEvent->capacity !== null) {
                $eventOccupied = Registration::query()
                    ->where('event_id', $lockedEvent->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count();

                if ($eventOccupied >= $lockedEvent->capacity) {
                    throw new DomainException('Kapacita události je již plná.');
                }
            }

            if ($ticketType->capacity !== null) {
                $ticketOccupied = Registration::query()
                    ->where('ticket_type_id', $ticketType->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count();

                if ($ticketOccupied >= $ticketType->capacity) {
                    throw new DomainException("Kapacita vstupného „{$ticketType->name}“ je již vyčerpána.");
                }
            }

            $isFree = (float) $ticketType->price === 0.0;

            $registration = new Registration([
                'event_id' => $lockedEvent->id,
                'user_id' => $user->id,
                'ticket_type_id' => $ticketType->id,
                'unit_price' => $ticketType->price,
                'status' => $isFree ? 'confirmed' : 'pending',
            ]);

            if ($isFree) {
                $registration->confirmed_at = now();
            }

            $registration->save();

            return $registration;
        });
    }

    public function cancelByUser(Registration $registration, User $user): void
    {
        if ($registration->user_id !== $user->id) {
            throw new DomainException('Nemůžete zrušit cizí registraci.');
        }

        if ($registration->status === 'cancelled') {
            throw new DomainException('Registrace je již zrušená.');
        }

        if ($registration->event->starts_at->isPast()) {
            throw new DomainException('Registraci nelze zrušit po začátku události.');
        }

        $registration->status = 'cancelled';
        $registration->cancelled_at = now();
        $registration->save();
    }

    public function claimPayment(Registration $registration, User $user): void
    {
        if ($registration->user_id !== $user->id) {
            throw new DomainException('Tuto akci může provést jen majitel registrace.');
        }

        if ($registration->status !== 'pending') {
            throw new DomainException('Označit úhradu lze jen u registrace čekající na platbu.');
        }

        $registration->payment_claimed_at = now();
        $registration->save();
    }

    public function confirmPayment(Registration $registration, User $manager): void
    {
        if ($registration->status !== 'pending') {
            throw new DomainException('Potvrdit lze jen registraci čekající na platbu.');
        }

        $registration->status = 'confirmed';
        $registration->confirmed_by = $manager->id;
        $registration->confirmed_at = now();
        $registration->save();
    }

    public function rejectPayment(Registration $registration): void
    {
        if ($registration->status !== 'pending') {
            throw new DomainException('Odmítnout lze jen registraci čekající na platbu.');
        }

        $registration->status = 'cancelled';
        $registration->cancelled_at = now();
        $registration->save();
    }
}
