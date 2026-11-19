@extends('layouts.app')

@section('title', 'Úvod')

@section('content')
    <div class="p-4 p-md-5 bg-white rounded border shadow-sm mb-4">
        <h1 class="h2 mb-3">Systém pro správu událostí</h1>
        <p class="lead text-muted mb-4">
            Objevujte koncerty, přednášky, workshopy a další akce nebo založte vlastní událost.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('events.index') }}" class="btn btn-primary">Procházet události</a>
            @auth
                <a href="{{ route('events.create') }}" class="btn btn-outline-primary">Vytvořit událost</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-outline-primary">Zaregistrovat se</a>
            @endauth
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Nejbližší události</h2>
        <a href="{{ route('events.index') }}" class="small text-decoration-none">Zobrazit všechny &rarr;</a>
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
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        @if ($event->cover_path)
                            <img src="{{ asset($event->cover_path) }}" class="card-img-top object-fit-cover" alt="{{ $event->title }}" style="height: 160px;">
                        @endif
                        <div class="card-body d-flex flex-column">
                            <div class="small text-muted mb-1">{{ $event->category?->name }} · {{ $startPrague->format('d. m. Y H:i') }}</div>
                            <h3 class="h5 card-title">
                                <a href="{{ route('events.show', $event->slug) }}" class="text-decoration-none text-dark">{{ $event->title }}</a>
                            </h3>
                            <p class="small text-muted mb-3">{{ $event->venue?->name }} ({{ $event->venue?->city }})</p>
                            <div class="mt-auto">
                                <a href="{{ route('events.show', $event->slug) }}" class="btn btn-sm btn-outline-primary">Detail události</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
