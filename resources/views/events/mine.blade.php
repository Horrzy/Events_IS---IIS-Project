@extends('layouts.app')

@section('title', 'Moje události')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Moje pořádané události</h1>
        <a href="{{ route('events.create') }}" class="btn btn-primary">Vytvořit novou událost</a>
    </div>

    @if ($errors->has('cancelled_reason'))
        <div class="alert alert-danger">{{ $errors->first('cancelled_reason') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Událost</th>
                        <th>Termín</th>
                        <th>Místo</th>
                        <th>Stav</th>
                        <th class="text-end">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        @php
                            $startPrague = $event->starts_at->copy()->setTimezone('Europe/Prague');
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('events.show', $event->slug) }}" class="fw-semibold text-decoration-none">
                                    {{ $event->title }}
                                </a>
                                <div class="small text-muted">{{ $event->code() }} · {{ $event->category?->name }}</div>
                                @if ($event->rejection_reason)
                                    <div class="small text-danger mt-1">Zamítnuto: {{ $event->rejection_reason }}</div>
                                @endif
                                @if ($event->cancelled_reason)
                                    <div class="small text-danger mt-1">Zrušeno: {{ $event->cancelled_reason }}</div>
                                @endif
                            </td>
                            <td class="small text-nowrap">{{ $startPrague->format('d. m. Y H:i') }}</td>
                            <td class="small">{{ $event->venue?->name }}</td>
                            <td>
                                @if ($event->status === 'approved')
                                    <span class="badge text-bg-success">Schváleno</span>
                                @elseif ($event->status === 'pending')
                                    <span class="badge text-bg-warning">Čeká na schválení</span>
                                @elseif ($event->status === 'rejected')
                                    <span class="badge text-bg-danger">Zamítnuto</span>
                                @elseif ($event->status === 'cancelled')
                                    <span class="badge text-bg-dark">Zrušeno</span>
                                @else
                                    <span class="badge text-bg-secondary">Koncept</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('registrations.participants', $event) }}" class="btn btn-sm btn-outline-secondary">
                                        Účastníci ({{ $event->active_registrations_count }})
                                    </a>

                                    @if ($event->status !== 'cancelled')
                                        <a href="{{ route('events.edit', $event) }}" class="btn btn-sm btn-outline-primary">Upravit</a>
                                    @endif

                                    @if (in_array($event->status, ['draft', 'rejected'], true))
                                        <form method="POST" action="{{ route('events.submit', $event) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Odeslat ke schválení</button>
                                        </form>
                                    @endif

                                    @if ($event->status !== 'cancelled')
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#cancel-event-{{ $event->id }}"
                                        >
                                            Zrušit
                                        </button>
                                    @endif
                                </div>

                                @if ($event->status !== 'cancelled')
                                    <div class="collapse mt-2 text-start" id="cancel-event-{{ $event->id }}">
                                        <form method="POST" action="{{ route('events.cancel', $event) }}" class="d-flex gap-2">
                                            @csrf
                                            <input
                                                type="text"
                                                name="cancelled_reason"
                                                class="form-control form-control-sm"
                                                placeholder="Důvod zrušení události..."
                                                required
                                            >
                                            <button type="submit" class="btn btn-sm btn-danger text-nowrap">Potvrdit zrušení</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Zatím nepořádáte žádnou událost.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($events->hasPages())
        <div class="mt-3">
            {{ $events->links() }}
        </div>
    @endif
@endsection
