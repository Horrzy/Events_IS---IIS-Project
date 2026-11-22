@extends('layouts.app')

@section('title', 'Stránka nenalezena')

@section('content')
    <div class="p-4 p-md-5 bg-white rounded border shadow-sm text-center">
        <h1 class="h3 mb-3">404 – Stránka nenalezena</h1>
        <p class="text-muted mb-4">
            Požadovaná stránka nebo záznam neexistuje, případně k němu nemáte přístup.
        </p>
        <a href="{{ route('home') }}" class="btn btn-primary">Zpět na úvod</a>
    </div>
@endsection
