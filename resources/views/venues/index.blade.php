@extends('layouts.app')

@section('title', 'Místa konání')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Místa konání</h1>
        @auth
            <a href="{{ route('venues.create') }}" class="btn btn-primary">Navrhnout nové místo</a>
        @endauth
    </div>

    <form method="GET" action="{{ route('venues.index') }}" class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="q" class="form-label small text-muted mb-1">Hledat podle názvu nebo adresy</label>
                    <input type="text" class="form-control" id="q" name="q" value="{{ $search }}" placeholder="Např. FIT VUT nebo Brno">
                </div>
                <div class="col-md-4">
                    <label for="city" class="form-label small text-muted mb-1">Město</label>
                    <select class="form-select" id="city" name="city">
                        <option value="">Všechna města</option>
                        @foreach ($cities as $cityOption)
                            <option value="{{ $cityOption }}" @selected($selectedCity === $cityOption)>{{ $cityOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">Filtrovat</button>
                    @if ($search !== '' || $selectedCity !== '')
                        <a href="{{ route('venues.index') }}" class="btn btn-outline-secondary">Zrušit</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    @if ($venues->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <p class="mb-2">Zatím zde nejsou žádná schválená místa konání.</p>
                @auth
                    <a href="{{ route('venues.create') }}" class="btn btn-sm btn-primary">Navrhnout první místo</a>
                @endauth
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($venues as $venue)
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        @if ($venue->photo_path)
                            <img
                                src="{{ asset($venue->photo_path) }}"
                                class="card-img-top object-fit-cover"
                                alt="{{ $venue->name }}"
                                style="height: 180px;"
                            >
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 card-title">{{ $venue->name }}</h2>
                            <p class="text-muted small mb-2">
                                {{ $venue->street }}, {{ $venue->city }}@if ($venue->postal_code) ({{ $venue->postal_code }})@endif
                            </p>
                            @if ($venue->description)
                                <p class="card-text text-secondary small mb-3">
                                    {{ \Illuminate\Support\Str::limit($venue->description, 120) }}
                                </p>
                            @endif
                            <div class="mt-auto">
                                <a href="{{ route('venues.show', $venue) }}" class="btn btn-sm btn-outline-primary">Zobrazit detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($venues->hasPages())
            <div class="mt-4">
                {{ $venues->links() }}
            </div>
        @endif
    @endif
@endsection
