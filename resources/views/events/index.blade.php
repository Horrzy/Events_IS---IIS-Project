@extends('layouts.app')

@section('title', 'Události')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Katalog událostí</h1>
        @auth
            <a href="{{ route('events.create') }}" class="btn btn-primary">Vytvořit událost</a>
        @endauth
    </div>

    <form method="GET" action="{{ route('events.index') }}" class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="q" class="form-label small text-muted mb-1">Hledat</label>
                    <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Název nebo popis">
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label small text-muted mb-1">Kategorie</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Všechny kategorie</option>
                        @foreach ($categoryTree as $item)
                            <option value="{{ $item['category']->id }}" @selected($filters['category_id'] === (string) $item['category']->id)>
                                {{ $item['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="venue_id" class="form-label small text-muted mb-1">Místo konání</label>
                    <select class="form-select" id="venue_id" name="venue_id">
                        <option value="">Všechna místa</option>
                        @foreach ($venues as $venue)
                            <option value="{{ $venue->id }}" @selected($filters['venue_id'] === (string) $venue->id)>
                                {{ $venue->name }} ({{ $venue->city }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="when" class="form-label small text-muted mb-1">Termín</label>
                    <select class="form-select" id="when" name="when">
                        <option value="upcoming" @selected($filters['when'] === 'upcoming')>Nadcházející</option>
                        <option value="past" @selected($filters['when'] === 'past')>Proběhlé</option>
                        <option value="all" @selected($filters['when'] === 'all')>Všechny</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="price" class="form-label small text-muted mb-1">Vstupné</label>
                    <select class="form-select" id="price" name="price">
                        <option value="all" @selected($filters['price'] === 'all')>Vše</option>
                        <option value="free" @selected($filters['price'] === 'free')>Zdarma</option>
                        <option value="paid" @selected($filters['price'] === 'paid')>Placené</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-outline-primary btn-sm">Filtrovat</button>
                <a href="{{ route('events.index') }}" class="btn btn-outline-secondary btn-sm">Vymazat filtry</a>
            </div>
        </div>
    </form>

    @if ($events->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <p class="mb-2">Zadaným filtrům neodpovídají žádné schválené události.</p>
                @auth
                    <a href="{{ route('events.create') }}" class="btn btn-sm btn-primary">Založit událost</a>
                @endauth
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($events as $event)
                @php
                    $minPrice = $event->ticketTypes->min('price');
                    $maxPrice = $event->ticketTypes->max('price');
                    $startPrague = $event->starts_at->copy()->setTimezone('Europe/Prague');
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card card-hover shadow-sm h-100">
                        @if ($event->cover_path)
                            <img
                                src="{{ asset($event->cover_path) }}"
                                class="card-img-top object-fit-cover"
                                alt="{{ $event->title }}"
                                style="height: 180px;"
                            >
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
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge text-bg-light border text-dark">{{ $event->category?->name }}</span>
                                    <span class="small text-muted">{{ $event->code() }}</span>
                                </div>
                            @endif
                            <h2 class="h5 card-title">
                                <a href="{{ route('events.show', $event->slug) }}" class="text-decoration-none text-dark">
                                    {{ $event->title }}
                                </a>
                            </h2>
                            <div class="small text-muted mb-2">
                                @if ($event->cover_path)
                                    <div>{{ $startPrague->format('d. m. Y H:i') }}</div>
                                @endif
                                <div>{{ $event->venue?->name }} ({{ $event->venue?->city }})</div>
                            </div>
                            <p class="card-text small text-secondary mb-3">
                                {{ \Illuminate\Support\Str::limit($event->description, 110) }}
                            </p>
                            <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-semibold small">
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

        @if ($events->hasPages())
            <div class="mt-4">
                {{ $events->links() }}
            </div>
        @endif
    @endif
@endsection
