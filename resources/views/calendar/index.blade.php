@extends('layouts.app')

@section('title', 'Můj kalendář')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Můj osobní kalendář</h1>
            <p class="text-muted small mb-0">Přehled událostí, na které jste přihlášeni nebo které pořádáte.</p>
        </div>
        <a href="{{ route('calendar.export') }}" class="btn btn-primary">Stáhnout kalendář (.ics)</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Nadcházející události ({{ $upcoming->count() }})</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Termín</th>
                        <th>Událost</th>
                        <th>Místo</th>
                        <th>Role</th>
                        <th class="text-end">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($upcoming as $item)
                        @php
                            $event = $item['event'];
                            $startPrague = $event->starts_at->copy()->setTimezone('Europe/Prague');
                            $endPrague = $event->ends_at->copy()->setTimezone('Europe/Prague');
                        @endphp
                        <tr>
                            <td class="small text-nowrap">
                                <div class="fw-semibold">{{ $startPrague->format('d. m. Y') }}</div>
                                <div class="text-muted">{{ $startPrague->format('H:i') }} – {{ $endPrague->format('H:i') }}</div>
                            </td>
                            <td>
                                <a href="{{ route('events.show', $event->slug) }}" class="fw-semibold text-decoration-none">
                                    {{ $event->title }}
                                </a>
                                <div class="small text-muted">{{ $event->category?->name }}</div>
                            </td>
                            <td class="small">{{ $event->venue?->name }} ({{ $event->venue?->city }})</td>
                            <td class="small">
                                <span class="badge text-bg-light border text-dark">{{ $item['relation'] }}</span>
                                @if ($item['registration_status'] === 'pending')
                                    <span class="badge text-bg-warning ms-1">Čeká na úhradu</span>
                                @elseif ($item['registration_status'] === 'confirmed')
                                    <span class="badge text-bg-success ms-1">Potvrzeno</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('events.show', $event->slug) }}" class="btn btn-sm btn-outline-primary">Otevřít</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">V kalendáři zatím nemáte žádné nadcházející události.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Proběhlé události ({{ $past->count() }})</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Termín</th>
                        <th>Událost</th>
                        <th>Místo</th>
                        <th>Role</th>
                        <th class="text-end">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($past as $item)
                        @php
                            $event = $item['event'];
                            $startPrague = $event->starts_at->copy()->setTimezone('Europe/Prague');
                        @endphp
                        <tr>
                            <td class="small text-nowrap">{{ $startPrague->format('d. m. Y H:i') }}</td>
                            <td>
                                <a href="{{ route('events.show', $event->slug) }}" class="fw-semibold text-decoration-none">
                                    {{ $event->title }}
                                </a>
                            </td>
                            <td class="small">{{ $event->venue?->name }} ({{ $event->venue?->city }})</td>
                            <td class="small">
                                <span class="badge text-bg-light border text-dark">{{ $item['relation'] }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('events.show', $event->slug) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Zatím žádné proběhlé události.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
