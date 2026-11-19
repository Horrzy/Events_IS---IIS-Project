@extends('layouts.app')

@section('title', 'Přístup zamítnut')

@section('content')
    <div class="p-4 p-md-5 bg-white rounded border shadow-sm text-center">
        <h1 class="h3 mb-3 text-danger">403 – Přístup zamítnut</h1>
        <p class="text-muted mb-4">
            {{ $exception->getMessage() ?: 'K této stránce nebo akci nemáte potřebné oprávnění.' }}
        </p>
        <a href="{{ route('home') }}" class="btn btn-primary">Zpět na úvod</a>
    </div>
@endsection
