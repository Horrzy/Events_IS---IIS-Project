@extends('layouts.app')

@section('title', $category ? 'Upravit kategorii' : 'Přidat kategorii')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $category ? "Upravit kategorii: {$category->name}" : 'Přidat novou kategorii' }}</h1>
        <a href="{{ route('moderation.categories.index') }}" class="btn btn-outline-secondary">Zpět na moderaci</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">Pole označená <span class="text-danger">*</span> jsou povinná.</p>

                    <form
                        method="POST"
                        action="{{ $category ? route('moderation.categories.update', $category) : route('moderation.categories.store') }}"
                        novalidate
                    >
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Název kategorie <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name', $category?->name) }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Nadřazená kategorie <span class="text-muted small">(nepovinné)</span></label>
                            <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                                <option value="">Žádná (hlavní kategorie)</option>
                                @foreach ($parentOptions as $option)
                                    <option
                                        value="{{ $option['category']->id }}"
                                        @selected((string) old('parent_id', $category?->parent_id) === (string) $option['category']->id)
                                    >
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Max. 4 úrovně.</div>
                            @error('parent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Popis <span class="text-muted small">(nepovinné)</span></label>
                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="3"
                            >{{ old('description', $category?->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            {{ $category ? 'Uložit změny' : 'Přidat a schválit kategorii' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
