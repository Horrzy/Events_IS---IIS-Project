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

        // 1. Organizer creates an event with capacity 1 and a paid ticket and submits it for approval
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

        // 2. Moderator approves the event
        $this->actingAs($moderator)->post("/moderace/udalosti/{$event->id}/schvalit")->assertRedirect();
        $event->refresh();
        $this->assertSame('approved', $event->status);

        // 3. Filtering by parent category includes events in its subcategory
        $this->get('/udalosti?category_id=' . $parentCat->id)
            ->assertStatus(200)
            ->assertSee('Testovací workshop');

        // 4. Participant registers for a paid ticket and starts in pending status
        $ticket = TicketType::where('event_id', $event->id)->firstOrFail();
        $this->actingAs($participant)
            ->post("/udalosti/{$event->slug}/registrace", ['ticket_type_id' => $ticket->id])
            ->assertRedirect("/udalosti/{$event->slug}");

        $registration = Registration::where('event_id', $event->id)->where('user_id', $participant->id)->firstOrFail();
        $this->assertSame('pending', $registration->status);

        // 5. Second participant is rejected because capacity is full (1/1)
        $this->actingAs($secondParticipant)
            ->post("/udalosti/{$event->slug}/registrace", ['ticket_type_id' => $ticket->id])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('registrations', [
            'event_id' => $event->id,
            'user_id' => $secondParticipant->id,
        ]);

        // 6. Event manager views participants list and confirms payment
        $this->actingAs($organizer)
            ->get("/udalosti/{$event->id}/ucastnici")
            ->assertStatus(200)
            ->assertSee($participant->name);
        $this->actingAs($organizer)
            ->post("/registrace/{$registration->id}/potvrdit")
            ->assertSessionHas('success');
        $registration->refresh();
        $this->assertSame('confirmed', $registration->status);

        // 6b. Manager cannot set ticket capacity higher than event capacity
        $this->actingAs($organizer)->post("/udalosti/{$event->id}/upravit", [
            'title' => 'Testovací workshop',
            'description' => 'Popis testovacího workshopu.',
            'starts_at' => now('Europe/Prague')->addDays(3)->format('Y-m-d\TH:i'),
            'ends_at' => now('Europe/Prague')->addDays(3)->addHours(2)->format('Y-m-d\TH:i'),
            'venue_id' => $venue->id,
            'category_id' => $childCat->id,
            'capacity' => 1,
            'tickets' => [
                ['id' => $ticket->id, 'name' => 'Placený vstup', 'price' => 200, 'capacity' => 5],
            ],
        ])->assertSessionHasErrors('capacity');

        // 6c. Editing an approved event returns it to pending moderation
        $this->actingAs($organizer)->post("/udalosti/{$event->id}/upravit", [
            'title' => 'Testovací workshop upravený',
            'description' => 'Popis testovacího workshopu.',
            'starts_at' => now('Europe/Prague')->addDays(3)->format('Y-m-d\TH:i'),
            'ends_at' => now('Europe/Prague')->addDays(3)->addHours(2)->format('Y-m-d\TH:i'),
            'venue_id' => $venue->id,
            'category_id' => $childCat->id,
            'capacity' => 2,
            'tickets' => [
                ['id' => $ticket->id, 'name' => 'Placený vstup', 'price' => 200, 'capacity' => 2],
            ],
        ])->assertRedirect('/moje-udalosti');
        $event->refresh();
        $this->assertSame('pending', $event->status);

        // Moderator approves the updated event again
        $this->actingAs($moderator)->post("/moderace/udalosti/{$event->id}/schvalit")->assertRedirect();
        $event->refresh();
        $this->assertSame('approved', $event->status);

        // 7. Personal .ics calendar export contains the event
        $this->actingAs($participant)
            ->get('/muj-kalendar.ics')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('BEGIN:VCALENDAR', false)
            ->assertSee('Testovací workshop upravený', false);

        // 8. Rating and commenting are blocked before the event ends
        $this->actingAs($participant)
            ->post("/udalosti/{$event->slug}/hodnoceni", ['stars' => 5])
            ->assertSessionHas('error');
        $this->assertDatabaseCount('ratings', 0);

        // 9. After the event ends, a confirmed participant can rate and comment (overwriting previous comment)
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
        $this->actingAs($participant)
            ->post("/udalosti/{$event->slug}/komentar", ['body' => 'Upravený komentář!'])
            ->assertRedirect("/udalosti/{$event->slug}");
        $this->assertDatabaseCount('comments', 1);

        $comment = Comment::where('event_id', $event->id)->firstOrFail();
        $this->assertSame('Upravený komentář!', $comment->body);
        $this->assertFalse($comment->is_hidden);

        // 10. Moderator hides the comment
        $this->actingAs($moderator)
            ->post("/moderace/komentare/{$comment->id}/skryt")
            ->assertSessionHas('success');
        $comment->refresh();
        $this->assertTrue($comment->is_hidden);

        // 11. Admin cannot delete a user who has ratings or comments
        $admin = $this->createUser('hlavniadmin', 'admin');
        $this->actingAs($admin)
            ->post("/admin/uzivatele/{$participant->id}/smazat")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $participant->id]);
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
