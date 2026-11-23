@extends('layouts.app')

@section('title', 'Posouzení události')

@section('content')
    @include('moderation._nav')

    @php
        $startPrague = $event->starts_at->copy()->setTimezone('Europe/Prague');
        $endPrague = $event->ends_at->copy()->setTimezone('Europe/Prague');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <span class="badge text-bg-secondary">{{ $event->code() }}</span>
            <h1 class="h3 mb-0 mt-1">{{ $event->title }}</h1>
        </div>
        <a href="{{ route('moderation.events.index') }}" class="btn btn-outline-secondary">Zpět na frontu</a>
    </div>

    @if ($collisions->isNotEmpty())
        <div class="alert alert-warning">
            <strong>Upozornění na kolizi místa a času:</strong> Ve stejném místě konání se v překrývajícím se termínu nachází jiná událost:
            <ul class="mb-0 mt-2">
                @foreach ($collisions as $collision)
                    <li>
                        <a href="{{ route('moderation.events.show', $collision) }}" class="alert-link">{{ $collision->title }}</a>
                        ({{ $collision->starts_at->copy()->setTimezone('Europe/Prague')->format('d. m. Y H:i') }}
                        – {{ $collision->ends_at->copy()->setTimezone('Europe/Prague')->format('H:i') }},
                        stav: {{ $collision->statusLabel() }})
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                @if ($event->cover_path)
                    <img src="{{ asset($event->cover_path) }}" class="card-img-top object-fit-cover" alt="{{ $event->title }}" style="max-height: 300px;">
                @endif
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Popis události</h2>
                    <div style="white-space: pre-line;">{{ $event->description }}</div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Typy vstupného</h2>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Název</th>
                                <th>Cena</th>
                                <th>Kapacita</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($event->ticketTypes as $ticket)
                                <tr>
                                    <td>{{ $ticket->name }}</td>
                                    <td>{{ (float) $ticket->price === 0.0 ? 'Zdarma' : number_format((float) $ticket->price, 2, ',', ' ') . ' Kč' }}</td>
                                    <td class="text-muted small">{{ $ticket->capacity ?? 'Dle celkové kapacity' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Souhrn</h2>
                    <dl class="small mb-0">
                        <dt class="text-muted">Stav</dt>
                        <dd>{{ $event->statusLabel() }}</dd>
                        <dt class="text-muted">Termín</dt>
                        <dd>{{ $startPrague->format('d. m. Y H:i') }} – {{ $endPrague->format('d. m. Y H:i') }}</dd>
                        <dt class="text-muted">Místo</dt>
                        <dd>{{ $event->venue?->name }} ({{ $event->venue?->street }}, {{ $event->venue?->city }})</dd>
                        <dt class="text-muted">Kategorie</dt>
                        <dd>{{ $event->category?->name }}</dd>
                        <dt class="text-muted">Kapacita</dt>
                        <dd>{{ $event->capacity ?? 'Neomezená' }}</dd>
                        <dt class="text-muted">Pořadatel</dt>
                        <dd class="mb-0">{{ $event->manager?->name }} ({{ $event->manager?->email }})</dd>
                    </dl>
                </div>
            </div>

            @if ($event->status !== 'cancelled')
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Rozhodnutí moderátora</h2>

                        @if ($event->status !== 'approved')
                            <form method="POST" action="{{ route('moderation.events.approve', $event) }}" class="mb-3">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">Schválit událost</button>
                            </form>
                        @endif

                        @if ($event->status !== 'rejected')
                            <form method="POST" action="{{ route('moderation.events.reject', $event) }}" novalidate>
                                @csrf
                                <div class="mb-2">
                                    <label for="rejection_reason" class="form-label small">Důvod zamítnutí <span class="text-danger">*</span></label>
                                    <textarea
                                        class="form-control form-control-sm @error('rejection_reason') is-invalid @enderror"
                                        id="rejection_reason"
                                        name="rejection_reason"
                                        rows="3"
                                        placeholder="Důvod zamítnutí"
                                        required
                                    >{{ old('rejection_reason') }}</textarea>
                                    @error('rejection_reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-outline-danger w-100">Zamítnout událost</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
