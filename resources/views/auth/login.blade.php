@extends('layouts.app')

@section('title', 'Přihlášení')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Přihlášení</h1>
                    <p class="text-muted small mb-4">Pole označená <span class="text-danger">*</span> jsou povinná.</p>

                    <form method="POST" action="{{ route('login.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="login" class="form-label">Uživatelské jméno nebo e-mail <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('login') is-invalid @enderror"
                                id="login"
                                name="login"
                                value="{{ old('login') }}"
                                required
                                autofocus
                            >
                            @error('login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Heslo <span class="text-danger">*</span></label>
                            <input
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                required
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Přihlásit se</button>
                    </form>

                    <p class="text-center text-muted small mt-3 mb-0">
                        Nemáte ještě účet? <a href="{{ route('register') }}">Zaregistrujte se</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
