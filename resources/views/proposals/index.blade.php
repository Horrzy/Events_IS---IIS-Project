@extends('layouts.app')

@section('title', 'Moje návrhy')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Moje návrhy</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('venues.create') }}" class="btn btn-primary">Navrhnout místo</a>
            <a href="{{ route('categories.create') }}" class="btn btn-outline-primary">Navrhnout kategorii</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Navržená místa konání</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Název</th>
                        <th>Adresa</th>
                        <th>Stav</th>
                        <th>Poznámka moderátora</th>
                        <th class="text-end">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($venues as $venue)
                        <tr>
                            <td class="fw-semibold">{{ $venue->name }}</td>
                            <td>{{ $venue->street }}, {{ $venue->city }}</td>
                            <td>
                                @if ($venue->status === 'approved')
                                    <span class="badge text-bg-success">Schváleno</span>
                                @elseif ($venue->status === 'rejected')
                                    <span class="badge text-bg-danger">Zamítnuto</span>
                                @else
                                    <span class="badge text-bg-warning">Čeká na schválení</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $venue->rejection_reason ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('venues.show', $venue) }}" class="btn btn-sm btn-outline-primary">Zobrazit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Zatím jste nenavrhli žádné místo konání.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Navržené kategorie událostí</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Název</th>
                        <th>Nadřazená kategorie</th>
                        <th>Stav</th>
                        <th>Poznámka moderátora</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td class="small">{{ $category->parent?->name ?? 'Hlavní kategorie' }}</td>
                            <td>
                                @if ($category->status === 'approved')
                                    <span class="badge text-bg-success">Schváleno</span>
                                @elseif ($category->status === 'rejected')
                                    <span class="badge text-bg-danger">Zamítnuto</span>
                                @else
                                    <span class="badge text-bg-warning">Čeká na schválení</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $category->rejection_reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Zatím jste nenavrhli žádnou kategorii.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
