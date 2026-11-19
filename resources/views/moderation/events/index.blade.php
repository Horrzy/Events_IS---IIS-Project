@extends('layouts.app')

@section('title', 'Moderace událostí')

@section('content')
    @include('moderation._nav')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Moderace událostí</h1>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}" href="{{ route('moderation.events.index', ['status' => 'pending']) }}">
                Čekající <span class="badge text-bg-warning ms-1">{{ $counts['pending'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}" href="{{ route('moderation.events.index', ['status' => 'approved']) }}">
                Schválené <span class="badge text-bg-success ms-1">{{ $counts['approved'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'rejected' ? 'active' : '' }}" href="{{ route('moderation.events.index', ['status' => 'rejected']) }}">
                Zamítnuté <span class="badge text-bg-danger ms-1">{{ $counts['rejected'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'cancelled' ? 'active' : '' }}" href="{{ route('moderation.events.index', ['status' => 'cancelled']) }}">
                Zrušené <span class="badge text-bg-dark ms-1">{{ $counts['cancelled'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('moderation.events.index', ['status' => 'all']) }}">
                Vše <span class="badge text-bg-secondary ms-1">{{ $counts['all'] }}</span>
            </a>
        </li>
    </ul>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Událost</th>
                        <th>Termín</th>
                        <th>Místo</th>
                        <th>Pořadatel</th>
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
                                <a href="{{ route('moderation.events.show', $event) }}" class="fw-semibold text-decoration-none">
                                    {{ $event->title }}
                                </a>
                                <div class="small text-muted">{{ $event->code() }} · {{ $event->category?->name }}</div>
                            </td>
                            <td class="small text-nowrap">{{ $startPrague->format('d. m. Y H:i') }}</td>
                            <td class="small">{{ $event->venue?->name }} ({{ $event->venue?->city }})</td>
                            <td class="small">{{ $event->manager?->name ?? '—' }}</td>
                            <td>
                                @if ($event->status === 'approved')
                                    <span class="badge text-bg-success">Schváleno</span>
                                @elseif ($event->status === 'pending')
                                    <span class="badge text-bg-warning">Čeká</span>
                                @elseif ($event->status === 'rejected')
                                    <span class="badge text-bg-danger">Zamítnuto</span>
                                @elseif ($event->status === 'cancelled')
                                    <span class="badge text-bg-dark">Zrušeno</span>
                                @else
                                    <span class="badge text-bg-secondary">Koncept</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('moderation.events.show', $event) }}" class="btn btn-sm btn-outline-primary">Posoudit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">V této záložce nejsou žádné události.</td>
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
