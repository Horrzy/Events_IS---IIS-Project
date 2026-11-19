@extends('layouts.app')

@section('title', 'Kategorie událostí')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Kategorie událostí</h1>
        @auth
            <a href="{{ route('categories.create') }}" class="btn btn-primary">Navrhnout novou kategorii</a>
        @endauth
    </div>

    <div class="card shadow-sm">
        @if ($tree === [])
            <div class="card-body text-center py-5 text-muted">
                <p class="mb-2">Zatím nejsou schválené žádné kategorie.</p>
                @auth
                    <a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary">Navrhnout první kategorii</a>
                @endauth
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($tree as $item)
                    @php
                        $category = $item['category'];
                        $depth = $item['depth'];
                    @endphp
                    <div class="list-group-item py-3" style="padding-left: {{ 1 + ($depth - 1) * 1.5 }}rem;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <span class="{{ $depth === 1 ? 'fw-semibold' : '' }}">
                                    @if ($depth > 1)
                                        <span class="text-muted me-1">↳</span>
                                    @endif
                                    {{ $category->name }}
                                </span>
                                @if ($category->description)
                                    <div class="small text-muted mt-1">{{ $category->description }}</div>
                                @endif
                            </div>
                            <span class="badge text-bg-light border text-muted">Úroveň {{ $depth }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
