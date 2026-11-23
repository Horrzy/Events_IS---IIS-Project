<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->upsertUser('admin', 'Administrátor', 'admin@iis.local', 'Admin1234!', 'admin');
        $moderator = $this->upsertUser('moderator', 'Moderátor Jan', 'moderator@iis.local', 'Mod1234!', 'moderator');
        $petr = $this->upsertUser('petr', 'Petr Dvořák', 'petr@iis.local', 'User1234!', 'user');
        $jana = $this->upsertUser('jana', 'Jana Svobodová', 'jana@iis.local', 'User1234!', 'user');

        // 1. Venues
        $fit = $this->upsertVenue('FIT VUT', 'Božetěchova 2', 'Brno', '612 66', 'Fakulta informačních technologií VUT v Brně, moderní posluchárny a auly.', 'approved', $admin->id, $moderator->id);
        $divadlo = $this->upsertVenue('Divadlo Husa na provázku', 'Zelný trh 9', 'Brno', '602 00', 'Komorní divadlo v historickém centru Brna.', 'approved', $admin->id, $moderator->id);
        $fleda = $this->upsertVenue('Klub Fléda', 'Štefánikova 24', 'Brno', '602 00', 'Hudební klub pro koncerty a večerní akce.', 'approved', $petr->id, $moderator->id);
        $semilasso = $this->upsertVenue('Kulturní centrum Semilasso', 'Palackého třída 126', 'Brno', '612 00', 'Kulturní centrum s více sály a dobrou dopravní dostupností.', 'approved', $jana->id, $moderator->id);
        $ntk = $this->upsertVenue('Národní technická knihovna', 'Technická 6', 'Praha', '160 80', 'Ballingův sál a studijní prostory pro odborné přednášky.', 'approved', $petr->id, $moderator->id);
        $this->upsertVenue('Kavárna Pražírna', 'Radnická 5', 'Brno', '602 00', 'Komorní prostor pro setkání a čtení.', 'pending', $jana->id, null);

        // 2. Hierarchical categories
        $kultura = $this->upsertCategory('Kultura', 'kultura', null, 'Kulturní a umělecké akce.', 'approved', $admin->id, $moderator->id);
        $hudba = $this->upsertCategory('Hudba', 'hudba', $kultura->id, 'Hudební vystoupení a večery.', 'approved', $admin->id, $moderator->id);
        $koncert = $this->upsertCategory('Koncert', 'koncert', $hudba->id, 'Živé koncerty kapel i sólistů.', 'approved', $admin->id, $moderator->id);
        $catDivadlo = $this->upsertCategory('Divadlo', 'divadlo', $kultura->id, 'Divadelní představení a improvizace.', 'approved', $admin->id, $moderator->id);

        $vzdelani = $this->upsertCategory('Vzdělání', 'vzdelani', null, 'Přednášky, kurzy a odborné akce.', 'approved', $admin->id, $moderator->id);
        $seminar = $this->upsertCategory('Seminář', 'seminar', $vzdelani->id, 'Odborné semináře a diskuse.', 'approved', $admin->id, $moderator->id);
        $workshop = $this->upsertCategory('Workshop', 'workshop', $seminar->id, 'Praktické workshopy s aktivním zapojením.', 'approved', $petr->id, $moderator->id);

        $sport = $this->upsertCategory('Sport', 'sport', null, 'Běhy, turnaje a sportovní setkání.', 'approved', $admin->id, $moderator->id);
        $tech = $this->upsertCategory('Technologie', 'technologie', null, 'IT konference, meetupy a hackathony.', 'approved', $admin->id, $moderator->id);
        $this->upsertCategory('Festival', 'festival', $kultura->id, 'Vícedenní hudební a kulturní festivaly.', 'pending', $jana->id, null);

        // 3. Events (past, upcoming, pending, draft)
        $pastConcert = $this->upsertEvent(
            'koncert-komorni-hudby',
            'Koncert komorní hudby',
            'Večer komorní hudby českých a evropských skladatelů v akustickém sále v centru Brna.',
            Carbon::now('UTC')->subDays(12)->setTime(17, 0),
            Carbon::now('UTC')->subDays(12)->setTime(20, 0),
            $divadlo->id,
            $koncert->id,
            80,
            'Platba předem na účet 123456789/0100, do poznámky uveďte své uživatelské jméno.',
            'approved',
            $petr->id,
            $moderator->id,
            [
                ['Základní vstupné', 200, 60],
                ['Studentské vstupné', 100, 20],
            ]
        );

        $upcomingWorkshop = $this->upsertEvent(
            'podzimni-webovy-workshop',
            'Podzimní webový workshop',
            'Praktický workshop pro začátečníky i mírně pokročilé zaměřený na tvorbu webových aplikací. Přineste si vlastní notebook.',
            Carbon::now('UTC')->addDays(5)->setTime(16, 0),
            Carbon::now('UTC')->addDays(5)->setTime(19, 30),
            $fit->id,
            $workshop->id,
            30,
            'Pro podporovatele a VIP balíček je účet 987654321/0100.',
            'approved',
            $petr->id,
            $moderator->id,
            [
                ['Základní vstup (zdarma)', 0, 25],
                ['VIP s občerstvením', 250, 5],
            ]
        );

        $upcomingTheatre = $this->upsertEvent(
            'divadelni-vecer-v-centru-brna',
            'Divadelní večer v centru Brna',
            'Autorské divadelní představení spojené s besedou s tvůrci po skončení hry.',
            Carbon::now('UTC')->addDays(10)->setTime(18, 0),
            Carbon::now('UTC')->addDays(10)->setTime(20, 30),
            $divadlo->id,
            $catDivadlo->id,
            50,
            'Úhrada převodem na účet 555666777/0800 nejpozději 2 dny před akcí.',
            'approved',
            $jana->id,
            $moderator->id,
            [
                ['Běžné místo', 280, null],
                ['Student / Senior', 160, 20],
            ]
        );

        $this->upsertEvent(
            'seminar-bezpecnosti-webu',
            'Seminář bezpečnosti webových aplikací',
            'Otevřená přednáška o běžných bezpečnostních chybách ve webových aplikacích a o tom, jak jim v praxi předcházet.',
            Carbon::now('UTC')->addDays(14)->setTime(15, 0),
            Carbon::now('UTC')->addDays(14)->setTime(17, 30),
            $ntk->id,
            $tech->id,
            null,
            null,
            'approved',
            $admin->id,
            $moderator->id,
            [
                ['Volný vstup', 0, null],
            ]
        );

        $this->upsertEvent(
            'rockovy-vecer-na-flede',
            'Rockový večer na Flédě',
            'Večerní koncert tří brněnských studentských kapel. Otevření klubu v 18:30, první kapela začíná v 19:00.',
            Carbon::now('UTC')->addDays(20)->setTime(19, 0),
            Carbon::now('UTC')->addDays(20)->setTime(23, 0),
            $fleda->id,
            $koncert->id,
            150,
            'Vstupné na účet 111222333/0100.',
            'pending',
            $jana->id,
            null,
            [
                ['Předprodej', 190, 150],
            ]
        );

        $this->upsertEvent(
            'jarni-sportovni-turnaj',
            'Jarní sportovní turnaj',
            'Celodenní amatérský turnaj v halových sportech pro týmy z Brna a okolí.',
            Carbon::now('UTC')->addDays(45)->setTime(9, 0),
            Carbon::now('UTC')->addDays(45)->setTime(17, 0),
            $semilasso->id,
            $sport->id,
            100,
            null,
            'draft',
            $petr->id,
            null,
            [
                ['Základní registrace', 0, null],
            ]
        );

        // 4. Registrations, ratings, and comments for the past event
        $concertTicket = $pastConcert->ticketTypes()->first();
        if ($concertTicket !== null) {
            $this->upsertRegistration($pastConcert->id, $jana->id, $concertTicket->id, (float) $concertTicket->price, 'confirmed', $petr->id);
            $this->upsertRegistration($pastConcert->id, $moderator->id, $concertTicket->id, (float) $concertTicket->price, 'confirmed', $petr->id);

            Rating::updateOrCreate(['event_id' => $pastConcert->id, 'user_id' => $jana->id], ['stars' => 5]);
            Rating::updateOrCreate(['event_id' => $pastConcert->id, 'user_id' => $moderator->id], ['stars' => 4]);

            if (!Comment::where('event_id', $pastConcert->id)->where('user_id', $jana->id)->exists()) {
                Comment::create([
                    'event_id' => $pastConcert->id,
                    'user_id' => $jana->id,
                    'body' => 'Skvělá atmosféra i akustika, moc jsme si koncert užili!',
                ]);
            }

            if (!Comment::where('event_id', $pastConcert->id)->where('user_id', $moderator->id)->exists()) {
                $hidden = Comment::create([
                    'event_id' => $pastConcert->id,
                    'user_id' => $moderator->id,
                    'body' => 'Levné vstupenky na všechny akce v ČR, mrkněte na náš eshop!',
                ]);
                $hidden->is_hidden = true;
                $hidden->hidden_by = $admin->id;
                $hidden->hidden_at = now();
                $hidden->save();
            }
        }

        // 5. Registrations for upcoming events (confirmed and pending payment)
        $freeWorkshopTicket = $upcomingWorkshop->ticketTypes()->where('price', 0)->first();
        $vipWorkshopTicket = $upcomingWorkshop->ticketTypes()->where('price', '>', 0)->first();
        if ($freeWorkshopTicket !== null) {
            $this->upsertRegistration($upcomingWorkshop->id, $jana->id, $freeWorkshopTicket->id, 0.0, 'confirmed', null);
        }
        if ($vipWorkshopTicket !== null) {
            $reg = $this->upsertRegistration($upcomingWorkshop->id, $moderator->id, $vipWorkshopTicket->id, (float) $vipWorkshopTicket->price, 'pending', null);
            if ($reg->payment_claimed_at === null) {
                $reg->payment_claimed_at = now()->subHours(2);
                $reg->save();
            }
        }

        $theatreTicket = $upcomingTheatre->ticketTypes()->first();
        if ($theatreTicket !== null) {
            $this->upsertRegistration($upcomingTheatre->id, $petr->id, $theatreTicket->id, (float) $theatreTicket->price, 'pending', null);
        }
    }

    private function upsertUser(string $username, string $name, string $email, string $password, string $role): User
    {
        $user = User::where('username', $username)->first();
        if ($user !== null) {
            return $user;
        }

        $user = new User([
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        return $user;
    }

    private function upsertVenue(
        string $name,
        string $street,
        string $city,
        ?string $postalCode,
        string $description,
        string $status,
        int $proposedBy,
        ?int $reviewedBy
    ): Venue {
        $venue = Venue::where('name', $name)->where('city', $city)->first();
        if ($venue !== null) {
            return $venue;
        }

        $venue = new Venue([
            'name' => $name,
            'street' => $street,
            'city' => $city,
            'postal_code' => $postalCode,
            'description' => $description,
        ]);
        $venue->status = $status;
        $venue->proposed_by = $proposedBy;
        $venue->reviewed_by = $reviewedBy;
        $venue->reviewed_at = $reviewedBy !== null ? now() : null;
        $venue->save();

        return $venue;
    }

    private function upsertCategory(
        string $name,
        string $slug,
        ?int $parentId,
        string $description,
        string $status,
        int $proposedBy,
        ?int $reviewedBy
    ): Category {
        $category = Category::where('slug', $slug)->first();
        if ($category !== null) {
            return $category;
        }

        $category = new Category([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'description' => $description,
        ]);
        $category->status = $status;
        $category->proposed_by = $proposedBy;
        $category->reviewed_by = $reviewedBy;
        $category->reviewed_at = $reviewedBy !== null ? now() : null;
        $category->save();

        return $category;
    }

    private function upsertEvent(
        string $slug,
        string $title,
        string $description,
        Carbon $startsAt,
        Carbon $endsAt,
        int $venueId,
        int $categoryId,
        ?int $capacity,
        ?string $paymentNote,
        string $status,
        int $managerId,
        ?int $reviewedBy,
        array $tickets
    ): Event {
        $event = Event::where('slug', $slug)->first();
        if ($event !== null) {
            return $event;
        }

        $event = new Event([
            'slug' => $slug,
            'title' => $title,
            'description' => $description,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'venue_id' => $venueId,
            'category_id' => $categoryId,
            'capacity' => $capacity,
            'payment_note' => $paymentNote,
        ]);
        $event->status = $status;
        $event->manager_id = $managerId;
        $event->submitted_at = $status !== 'draft' ? now()->subDays(2) : null;
        $event->reviewed_by = $reviewedBy;
        $event->reviewed_at = $reviewedBy !== null ? now()->subDay() : null;
        $event->save();

        foreach ($tickets as [$name, $price, $ticketCapacity]) {
            TicketType::create([
                'event_id' => $event->id,
                'name' => $name,
                'price' => $price,
                'capacity' => $ticketCapacity,
            ]);
        }

        return $event;
    }

    private function upsertRegistration(
        int $eventId,
        int $userId,
        int $ticketTypeId,
        float $unitPrice,
        string $status,
        ?int $confirmedBy
    ): Registration {
        $existing = Registration::where('event_id', $eventId)->where('user_id', $userId)->first();
        if ($existing !== null) {
            return $existing;
        }

        $reg = new Registration([
            'event_id' => $eventId,
            'user_id' => $userId,
            'ticket_type_id' => $ticketTypeId,
            'unit_price' => $unitPrice,
            'status' => $status,
        ]);
        $reg->confirmed_by = $confirmedBy;
        $reg->confirmed_at = $status === 'confirmed' ? now()->subDay() : null;
        $reg->save();

        return $reg;
    }
}
