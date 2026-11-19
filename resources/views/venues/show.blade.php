@extends('layouts.app')

@section('title', $venue->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $venue->name }}</h1>
            <p class="text-muted mb-0">
                {{ $venue->street }}, {{ $venue->city }}@if ($venue->postal_code), {{ $venue->postal_code }}@endif
            </p>
        </div>
        <a href="{{ route('venues.index') }}" class="btn btn-outline-secondary">Zpět na místa</a>
    </div>

    @if ($venue->status === 'pending')
        <div class="alert alert-warning">
            Toto místo zatím <strong>čeká na schválení moderátorem</strong>. Veřejně se zobrazí až po schválení.
        </div>
    @elseif ($venue->status === 'rejected')
        <div class="alert alert-danger">
            <strong>Tento návrh místa byl zamítnut.</strong>
            @if ($venue->rejection_reason)
                <div class="mt-1">Důvod: {{ $venue->rejection_reason }}</div>
            @endif
        </div>
    @endif

    <div class="card shadow-sm">
        @if ($venue->photo_path)
            <img
                src="{{ asset($venue->photo_path) }}"
                class="card-img-top object-fit-cover"
                alt="{{ $venue->name }}"
                style="max-height: 360px;"
            >
        @endif
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Informace o místě</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3 text-muted">Adresa</dt>
                <dd class="col-sm-9">
                    {{ $venue->street }}, {{ $venue->city }}@if ($venue->postal_code) ({{ $venue->postal_code }})@endif
                </dd>

                @if ($venue->description)
                    <dt class="col-sm-3 text-muted">Popis</dt>
                    <dd class="col-sm-9">{{ $venue->description }}</dd>
                @endif

                <dt class="col-sm-3 text-muted">Navrhl</dt>
                <dd class="col-sm-9 mb-0">{{ $venue->proposer?->name ?? 'Neznámý uživatel' }}</dd>
            </dl>
        </div>
    </div>
@endsection
