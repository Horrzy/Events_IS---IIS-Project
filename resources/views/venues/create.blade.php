@extends('layouts.app')

@section('title', 'Navrhnout nové místo')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Navrhnout nové místo konání</h1>
        <a href="{{ route('venues.index') }}" class="btn btn-outline-secondary">Zpět na místa</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">
                        Navržené místo zkontroluje a schválí moderátor. Pole označená <span class="text-danger">*</span> jsou povinná.
                    </p>

                    <form method="POST" action="{{ route('venues.store') }}" enctype="multipart/form-data" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Název místa <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Název místa"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="street" class="form-label">Ulice a číslo <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control @error('street') is-invalid @enderror"
                                    id="street"
                                    name="street"
                                    value="{{ old('street') }}"
                                    placeholder="Ulice a číslo"
                                    required
                                >
                                @error('street')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="city" class="form-label">Město <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control @error('city') is-invalid @enderror"
                                    id="city"
                                    name="city"
                                    value="{{ old('city') }}"
                                    placeholder="Město"
                                    required
                                >
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="postal_code" class="form-label">PSČ</label>
                                <input
                                    type="text"
                                    class="form-control @error('postal_code') is-invalid @enderror"
                                    id="postal_code"
                                    name="postal_code"
                                    value="{{ old('postal_code') }}"
                                    placeholder="PSČ"
                                >
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Popis místa <span class="text-muted small">(nepovinné)</span></label>
                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Popis místa..."
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="photo" class="form-label">Fotka místa <span class="text-muted small">(nepovinné, max 2 MB)</span></label>
                            <input
                                type="file"
                                class="form-control @error('photo') is-invalid @enderror"
                                id="photo"
                                name="photo"
                                accept="image/jpeg,image/png,image/webp"
                            >
                            @error('photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Odeslat návrh ke schválení</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
