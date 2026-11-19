<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_event_registration_capacity_payment_calendar_and_feedback_flow(): void
    {
        $moderator = $this->createUser('mod', 'moderator');
        $organizer = $this->createUser('org', 'user');
        $participant = $this->createUser('ucastnik', 'user');
        $secondParticipant = $this->createUser('druhy', 'user');

        $venue = new Venue(['name' => 'FIT VUT', 'street' => 'Božetěchova 2', 'city' => 'Brno']);
        $venue->status = 'approved';
        $venue->proposed_by = $organizer->id;
        $venue->reviewed_by = $moderator->id;
        $venue->save();

        $parentCat = new Category(['name' => 'Vzdělání', 'slug' => 'vzdelani']);
        $parentCat->status = 'approved';
        $parentCat->proposed_by = $moderator->id;
        $parentCat->save();

        $childCat = new Category(['name' => 'Workshop', 'slug' => 'workshop', 'parent_id' => $parentCat->id]);
        $childCat->status = 'approved';
        $childCat->proposed_by = $moderator->id;
        $childCat->save();

        // 1. Pořadatel založí událost s kapacitou 1 a placenou vstupenkou a odešle ke schválení
        $this->actingAs($organizer)->post('/udalosti/nova', [
            'title' => 'Testovací workshop',
            'description' => 'Popis testovacího workshopu.',
            'starts_at' => now('Europe/Prague')->addDays(3)->format('Y-m-d\TH:i'),
            'ends_at' => now('Europe/Prague')->addDays(3)->addHours(2)->format('Y-m-d\TH:i'),
            'venue_id' => $venue->id,
            'category_id' => $childCat->id,
            'capacity' => 1,
            'action' => 'submit',
            'tickets' => [
                ['name' => 'Placený vstup', 'price' => 200, 'capacity' => 1],
            ],
        ])->assertRedirect('/moje-udalosti');

        $event = Event::where('title', 'Testovací workshop')->firstOrFail();
        $this->assertSame('pending', $event->status);

        // 2. Moderátor událost schválí
        $this->actingAs($moderator)->post("/moderace/udalosti/{$event->id}/schvalit")->assertRedirect();
        $event->refresh();
        $this->assertSame('approved', $event->status);

        // 3. Filtrování podle nadřazené kategorie najde událost v podkategorii
        $this->get('/udalosti?category_id=' . $parentCat->id)
            ->assertStatus(200)
            ->assertSee('Testovací workshop');

        // 4. Účastník se zaregistruje -> stav pending (placená)
        $ticket = TicketType::where('event_id', $event->id)->firstOrFail();
        $this->actingAs($participant)
            ->post("/udalosti/{$event->slug}/registrace", ['ticket_type_id' => $ticket->id])
            ->assertRedirect("/udalosti/{$event->slug}");

        $registration = Registration::where('event_id', $event->id)->where('user_id', $participant->id)->firstOrFail();
        $this->assertSame('pending', $registration->status);

        // 5. Druhý účastník narazí na plnou kapacitu (1/1)
        $this->actingAs($secondParticipant)
            ->post("/udalosti/{$event->slug}/registrace", ['ticket_type_id' => $ticket->id])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('registrations', [
            'event_id' => $event->id,
            'user_id' => $secondParticipant->id,
        ]);

        // 6. Správce události potvrdí úhradu
        $this->actingAs($organizer)
            ->post("/registrace/{$registration->id}/potvrdit")
            ->assertSessionHas('success');
        $registration->refresh();
        $this->assertSame('confirmed', $registration->status);

        // 7. Export osobního kalendáře .ics obsahuje událost
        $this->actingAs($participant)
            ->get('/muj-kalendar.ics')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('BEGIN:VCALENDAR', false)
            ->assertSee('Testovací workshop', false);

        // 8. Před koncem události nelze hodnotit ani komentovat
        $this->actingAs($participant)
            ->post("/udalosti/{$event->slug}/hodnoceni", ['stars' => 5])
            ->assertSessionHas('error');
        $this->assertDatabaseCount('ratings', 0);

        // 9. Po skončení události může potvrzený účastník hodnotit i komentovat
        $event->starts_at = now()->subDays(2);
        $event->ends_at = now()->subDay();
        $event->save();

        $this->actingAs($participant)
            ->post("/udalosti/{$event->slug}/hodnoceni", ['stars' => 5])
            ->assertRedirect("/udalosti/{$event->slug}");
        $this->assertDatabaseHas('ratings', ['event_id' => $event->id, 'user_id' => $participant->id, 'stars' => 5]);

        $this->actingAs($participant)
            ->post("/udalosti/{$event->slug}/komentar", ['body' => 'Výborná akce!'])
            ->assertRedirect("/udalosti/{$event->slug}");

        $comment = Comment::where('event_id', $event->id)->firstOrFail();
        $this->assertFalse($comment->is_hidden);

        // 10. Moderátor skryje komentář
        $this->actingAs($moderator)
            ->post("/moderace/komentare/{$comment->id}/skryt")
            ->assertSessionHas('success');
        $comment->refresh();
        $this->assertTrue($comment->is_hidden);
    }

    private function createUser(string $username, string $role): User
    {
        $user = new User([
            'username' => $username,
            'name' => ucfirst($username),
            'email' => $username . '@example.test',
            'password' => 'Heslo1234!',
        ]);
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        return $user;
    }
}
