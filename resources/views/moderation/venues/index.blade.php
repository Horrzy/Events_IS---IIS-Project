@extends('layouts.app')

@section('title', 'Moderace míst konání')

@section('content')
    @include('moderation._nav')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Moderace a správa míst konání</h1>
        <a href="{{ route('moderation.venues.create') }}" class="btn btn-primary">Přidat místo</a>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}" href="{{ route('moderation.venues.index', ['status' => 'pending']) }}">
                Čekající <span class="badge text-bg-warning ms-1">{{ $counts['pending'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}" href="{{ route('moderation.venues.index', ['status' => 'approved']) }}">
                Schválená <span class="badge text-bg-success ms-1">{{ $counts['approved'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'rejected' ? 'active' : '' }}" href="{{ route('moderation.venues.index', ['status' => 'rejected']) }}">
                Zamítnutá <span class="badge text-bg-danger ms-1">{{ $counts['rejected'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('moderation.venues.index', ['status' => 'all']) }}">
                Vše <span class="badge text-bg-secondary ms-1">{{ $counts['all'] }}</span>
            </a>
        </li>
    </ul>

    @if ($errors->has('rejection_reason'))
        <div class="alert alert-danger">{{ $errors->first('rejection_reason') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Místo</th>
                        <th>Adresa</th>
                        <th>Navrhl</th>
                        <th>Stav</th>
                        <th class="text-end">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($venues as $venue)
                        <tr>
                            <td>
                                <a href="{{ route('venues.show', $venue) }}" class="fw-semibold text-decoration-none">
                                    {{ $venue->name }}
                                </a>
                                @if ($venue->rejection_reason)
                                    <div class="small text-danger mt-1">Důvod: {{ $venue->rejection_reason }}</div>
                                @endif
                            </td>
                            <td>{{ $venue->street }}, {{ $venue->city }}</td>
                            <td class="small">{{ $venue->proposer?->name ?? '—' }}</td>
                            <td>
                                @if ($venue->status === 'approved')
                                    <span class="badge text-bg-success">Schváleno</span>
                                @elseif ($venue->status === 'rejected')
                                    <span class="badge text-bg-danger">Zamítnuto</span>
                                @else
                                    <span class="badge text-bg-warning">Čeká</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    @if ($venue->status !== 'approved')
                                        <form method="POST" action="{{ route('moderation.venues.approve', $venue) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Schválit</button>
                                        </form>
                                    @endif

                                    @if ($venue->status !== 'rejected')
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#reject-venue-{{ $venue->id }}"
                                        >
                                            Zamítnout
                                        </button>
                                    @endif

                                    <a href="{{ route('moderation.venues.edit', $venue) }}" class="btn btn-sm btn-outline-primary">Upravit</a>

                                    <form method="POST" action="{{ route('moderation.venues.destroy', $venue) }}" onsubmit="return confirm('Opravdu chcete toto místo smazat?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Smazat</button>
                                    </form>
                                </div>

                                @if ($venue->status !== 'rejected')
                                    <div class="collapse mt-2 text-start" id="reject-venue-{{ $venue->id }}">
                                        <form method="POST" action="{{ route('moderation.venues.reject', $venue) }}" class="d-flex gap-2">
                                            @csrf
                                            <input
                                                type="text"
                                                name="rejection_reason"
                                                class="form-control form-control-sm"
                                                placeholder="Důvod zamítnutí..."
                                                required
                                            >
                                            <button type="submit" class="btn btn-sm btn-danger text-nowrap">Potvrdit zamítnutí</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">V této kategorii nejsou žádná místa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($venues->hasPages())
        <div class="mt-3">
            {{ $venues->links() }}
        </div>
    @endif
@endsection
