@extends('layouts.app')

@section('title', 'Moje registrace')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Moje registrace na události</h1>
        <a href="{{ route('calendar.index') }}" class="btn btn-outline-primary">Můj kalendář</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Událost</th>
                        <th>Termín</th>
                        <th>Vstupné</th>
                        <th>Stav</th>
                        <th class="text-end">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $reg)
                        @php
                            $event = $reg->event;
                            $startPrague = $event?->starts_at?->copy()->setTimezone('Europe/Prague');
                        @endphp
                        <tr>
                            <td>
                                @if ($event)
                                    <a href="{{ route('events.show', $event->slug) }}" class="fw-semibold text-decoration-none">
                                        {{ $event->title }}
                                    </a>
                                    <div class="small text-muted">{{ $event->venue?->name }} ({{ $event->venue?->city }})</div>
                                    @if ($reg->status === 'pending' && $event->payment_note)
                                        <div class="small text-secondary mt-1">
                                            <strong>Platba:</strong> {{ $event->payment_note }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="small text-nowrap">{{ $startPrague?->format('d. m. Y H:i') ?? '—' }}</td>
                            <td class="small">
                                <div>{{ $reg->ticketType?->name ?? '—' }}</div>
                                <div class="text-muted">
                                    {{ (float) $reg->unit_price === 0.0 ? 'Zdarma' : number_format((float) $reg->unit_price, 2, ',', ' ') . ' Kč' }}
                                </div>
                            </td>
                            <td>
                                @if ($reg->status === 'confirmed')
                                    <span class="badge text-bg-success">Potvrzeno</span>
                                @elseif ($reg->status === 'pending')
                                    <span class="badge text-bg-warning">Čeká na úhradu</span>
                                    @if ($reg->payment_claimed_at)
                                        <div class="small text-muted mt-1">Úhrada nahlášena</div>
                                    @endif
                                @else
                                    <span class="badge text-bg-secondary">Zrušeno</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    @if ($reg->status === 'pending' && !$reg->payment_claimed_at)
                                        <form method="POST" action="{{ route('registrations.claim-payment', $reg) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">Uhradil jsem</button>
                                        </form>
                                    @endif

                                    @if ($reg->status !== 'cancelled' && $event && $event->starts_at->isFuture())
                                        <form method="POST" action="{{ route('registrations.cancel', $reg) }}" onsubmit="return confirm('Opravdu chcete zrušit svoji registraci?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Zrušit</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Zatím nejste přihlášeni na žádnou událost.
                                <div class="mt-2">
                                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-primary">Procházet události</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($registrations->hasPages())
        <div class="mt-3">
            {{ $registrations->links() }}
        </div>
    @endif
@endsection
