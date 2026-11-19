@extends('layouts.app')

@section('title', 'Moderace kategorií')

@section('content')
    @include('moderation._nav')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Moderace a správa kategorií</h1>
        <a href="{{ route('moderation.categories.create') }}" class="btn btn-primary">Přidat kategorii</a>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}" href="{{ route('moderation.categories.index', ['status' => 'pending']) }}">
                Čekající <span class="badge text-bg-warning ms-1">{{ $counts['pending'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}" href="{{ route('moderation.categories.index', ['status' => 'approved']) }}">
                Schválené <span class="badge text-bg-success ms-1">{{ $counts['approved'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'rejected' ? 'active' : '' }}" href="{{ route('moderation.categories.index', ['status' => 'rejected']) }}">
                Zamítnuté <span class="badge text-bg-danger ms-1">{{ $counts['rejected'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" href="{{ route('moderation.categories.index', ['status' => 'all']) }}">
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
                        <th>Kategorie</th>
                        <th>Nadřazená</th>
                        <th>Navrhl</th>
                        <th>Stav</th>
                        <th class="text-end">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $category->name }}</div>
                                @if ($category->description)
                                    <div class="small text-muted">{{ $category->description }}</div>
                                @endif
                                @if ($category->rejection_reason)
                                    <div class="small text-danger mt-1">Důvod: {{ $category->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="small">{{ $category->parent?->name ?? 'Hlavní kategorie' }}</td>
                            <td class="small">{{ $category->proposer?->name ?? '—' }}</td>
                            <td>
                                @if ($category->status === 'approved')
                                    <span class="badge text-bg-success">Schváleno</span>
                                @elseif ($category->status === 'rejected')
                                    <span class="badge text-bg-danger">Zamítnuto</span>
                                @else
                                    <span class="badge text-bg-warning">Čeká</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    @if ($category->status !== 'approved')
                                        <form method="POST" action="{{ route('moderation.categories.approve', $category) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Schválit</button>
                                        </form>
                                    @endif

                                    @if ($category->status !== 'rejected')
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#reject-category-{{ $category->id }}"
                                        >
                                            Zamítnout
                                        </button>
                                    @endif

                                    <a href="{{ route('moderation.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Upravit</a>

                                    <form method="POST" action="{{ route('moderation.categories.destroy', $category) }}" onsubmit="return confirm('Opravdu chcete tuto kategorii smazat?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Smazat</button>
                                    </form>
                                </div>

                                @if ($category->status !== 'rejected')
                                    <div class="collapse mt-2 text-start" id="reject-category-{{ $category->id }}">
                                        <form method="POST" action="{{ route('moderation.categories.reject', $category) }}" class="d-flex gap-2">
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
                            <td colspan="5" class="text-center text-muted py-4">V této záložce nejsou žádné kategorie.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($categories->hasPages())
        <div class="mt-3">
            {{ $categories->links() }}
        </div>
    @endif
@endsection
