@extends('layouts.app')

@section('title', 'Účastníci události')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <span class="badge text-bg-secondary">{{ $event->code() }}</span>
            <h1 class="h3 mb-0 mt-1">Účastníci: {{ $event->title }}</h1>
            <p class="text-muted small mb-0">
                {{ $event->starts_at->copy()->setTimezone('Europe/Prague')->format('d. m. Y H:i') }}
                · {{ $event->venue?->name }}
                · Kapacita:
                @if ($event->capacity === null)
                    neomezená
                @else
                    {{ $registrations->whereIn('status', ['pending', 'confirmed'])->count() }} / {{ $event->capacity }}
                @endif
            </p>
        </div>
        <a href="{{ route('events.mine') }}" class="btn btn-outline-secondary">Zpět na moje události</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Účastník</th>
                        <th>Vstupné</th>
                        <th>Cena</th>
                        <th>Stav</th>
                        <th>Úhrada</th>
                        <th class="text-end">Akce správce</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $reg)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $reg->user?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $reg->user?->username }} · {{ $reg->user?->email }}</div>
                            </td>
                            <td class="small">{{ $reg->ticketType?->name ?? '—' }}</td>
                            <td class="small text-nowrap">
                                {{ (float) $reg->unit_price === 0.0 ? 'Zdarma' : number_format((float) $reg->unit_price, 2, ',', ' ') . ' Kč' }}
                            </td>
                            <td>
                                @if ($reg->status === 'confirmed')
                                    <span class="badge text-bg-success">Potvrzeno</span>
                                @elseif ($reg->status === 'pending')
                                    <span class="badge text-bg-warning">Čeká na úhradu</span>
                                @else
                                    <span class="badge text-bg-secondary">Zrušeno</span>
                                @endif
                            </td>
                            <td class="small">
                                @if ($reg->status === 'confirmed')
                                    {{ $reg->confirmed_at?->copy()->setTimezone('Europe/Prague')->format('d. m. Y H:i') ?? 'Potvrzeno' }}
                                @elseif ($reg->payment_claimed_at)
                                    <span class="text-success fw-semibold">Účastník nahlásil úhradu</span>
                                    <div class="text-muted">{{ $reg->payment_claimed_at->copy()->setTimezone('Europe/Prague')->format('d. m. Y H:i') }}</div>
                                @elseif ($reg->status === 'pending')
                                    <span class="text-muted">Zatím nenahlášeno</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($reg->status === 'pending')
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="{{ route('registrations.confirm-payment', $reg) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Potvrdit úhradu</button>
                                        </form>
                                        <form method="POST" action="{{ route('registrations.reject-payment', $reg) }}" onsubmit="return confirm('Odmítnout registraci a uvolnit místo?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Odmítnout</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="small text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Na tuto událost se zatím nikdo neregistroval.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
