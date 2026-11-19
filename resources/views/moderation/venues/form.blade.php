@extends('layouts.app')

@section('title', $venue ? 'Upravit místo' : 'Přidat místo')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $venue ? "Upravit místo: {$venue->name}" : 'Přidat nové místo konání' }}</h1>
        <a href="{{ route('moderation.venues.index') }}" class="btn btn-outline-secondary">Zpět na moderaci</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">Pole označená <span class="text-danger">*</span> jsou povinná.</p>

                    <form
                        method="POST"
                        action="{{ $venue ? route('moderation.venues.update', $venue) : route('moderation.venues.store') }}"
                        enctype="multipart/form-data"
                        novalidate
                    >
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Název místa <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name', $venue?->name) }}"
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
                                    value="{{ old('street', $venue?->street) }}"
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
                                    value="{{ old('city', $venue?->city) }}"
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
                                    value="{{ old('postal_code', $venue?->postal_code) }}"
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
                            >{{ old('description', $venue?->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($venue?->photo_path)
                            <div class="mb-3">
                                <div class="mb-2">
                                    <img src="{{ asset($venue->photo_path) }}" alt="{{ $venue->name }}" class="img-thumbnail" style="max-height: 160px;">
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                                    <label class="form-check-label" for="remove_photo">Odebrat stávající fotku</label>
                                </div>
                            </div>
                        @endif

                        <div class="mb-4">
                            <label for="photo" class="form-label">
                                {{ $venue?->photo_path ? 'Nahradit fotku' : 'Fotka místa' }}
                                <span class="text-muted small">(nepovinné – JPG, PNG, WebP, max 2 MB)</span>
                            </label>
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

                        <button type="submit" class="btn btn-primary">{{ $venue ? 'Uložit změny' : 'Přidat a schválit místo' }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
