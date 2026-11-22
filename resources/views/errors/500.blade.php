@extends('layouts.app')

@section('title', 'Chyba serveru')

@section('content')
    <div class="p-4 p-md-5 bg-white rounded border shadow-sm text-center">
        <h1 class="h3 mb-3 text-danger">500 – Chyba serveru</h1>
        <p class="text-muted mb-4">
            Při zpracování požadavku došlo k neočekávané chybě. Zkuste to prosím znovu.
        </p>
        <a href="{{ route('home') }}" class="btn btn-primary">Zpět na úvod</a>
    </div>
@endsection
