@extends('layouts.app')

@section('title', 'Úvod')

@section('content')
    <div class="hero-card p-4 p-md-5 shadow-sm mb-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge hero-badge mb-2">
                    Kulturní, vzdělávací i společenské akce
                </span>
                <h1 class="display-6 fw-bold mb-3 text-white">Objevujte události nebo uspořádejte vlastní</h1>
                <p class="mb-4" style="color: rgba(255, 255, 255, 0.82);">
                    Procházejte koncerty, přednášky, workshopy a setkání podle hierarchických kategorií, registrujte se online a mějte svůj osobní kalendář vždy po ruce.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('events.index') }}" class="btn btn-sapling px-4">Procházet katalog</a>
                    @auth
                        <a href="{{ route('events.create') }}" class="btn btn-outline-light px-4">+ Vytvořit událost</a>
                        <a href="{{ route('calendar.index') }}" class="btn btn-outline-light">Můj kalendář</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-outline-light px-4">Vytvořit účet</a>
                    @endauth
                </div>
            </div>
            <div class="col-lg-5">
                <form method="GET" action="{{ route('events.index') }}" class="card shadow-sm border-0 text-dark">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-semibold mb-3">Rychlé vyhledávání události</h2>
                        <div class="mb-3">
                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="Název akce, téma nebo klíčové slovo..."
                            >
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Vyhledat</button>
                            <a href="{{ route('categories.index') }}" class="btn btn-light border">Kategorie</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 fw-semibold mb-0">Nejbližší nadcházející události</h2>
        <a href="{{ route('events.index') }}" class="small text-decoration-none fw-semibold">Zobrazit všechny &rarr;</a>
    </div>

    @if ($upcomingEvents->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-4 text-muted">
                Zatím nejsou vypsány žádné nadcházející schválené události.
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($upcomingEvents as $event)
                @php
                    $startPrague = $event->starts_at->copy()->setTimezone('Europe/Prague');
                    $minPrice = $event->ticketTypes->min('price');
                    $maxPrice = $event->ticketTypes->max('price');
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card card-hover shadow-sm h-100">
                        @if ($event->cover_path)
                            <img src="{{ asset($event->cover_path) }}" class="card-img-top object-fit-cover" alt="{{ $event->title }}" style="height: 150px;">
                        @else
                            <div class="card-cover-placeholder">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-white text-primary border">{{ $event->category?->name }}</span>
                                    <span class="small text-secondary fw-semibold">{{ $event->code() }}</span>
                                </div>
                                <div class="small fw-semibold text-primary">
                                    {{ $startPrague->format('d. m. Y · H:i') }}
                                </div>
                            </div>
                        @endif
                        <div class="card-body d-flex flex-column">
                            @if ($event->cover_path)
                                <div class="small text-muted mb-1">{{ $event->category?->name }} · {{ $startPrague->format('d. m. Y H:i') }}</div>
                            @endif
                            <h3 class="h5 card-title mb-2">
                                <a href="{{ route('events.show', $event->slug) }}" class="text-decoration-none text-dark">{{ $event->title }}</a>
                            </h3>
                            <p class="small text-muted mb-3">{{ $event->venue?->name }} ({{ $event->venue?->city }})</p>
                            <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="small fw-semibold text-secondary">
                                    @if ($maxPrice === null || (float) $maxPrice === 0.0)
                                        Zdarma
                                    @elseif ((float) $minPrice === (float) $maxPrice)
                                        {{ number_format((float) $minPrice, 0, ',', ' ') }} Kč
                                    @else
                                        od {{ number_format((float) $minPrice, 0, ',', ' ') }} Kč
                                    @endif
                                </span>
                                <a href="{{ route('events.show', $event->slug) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
