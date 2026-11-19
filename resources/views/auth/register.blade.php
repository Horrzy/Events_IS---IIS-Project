@extends('layouts.app')

@section('title', 'Registrace')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Registrace nového účtu</h1>
                    <p class="text-muted small mb-4">Pole označená <span class="text-danger">*</span> jsou povinná.</p>

                    <form method="POST" action="{{ route('register.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="username" class="form-label">Uživatelské jméno <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('username') is-invalid @enderror"
                                id="username"
                                name="username"
                                value="{{ old('username') }}"
                                required
                                autofocus
                            >
                            <div class="form-text">Alespoň 3 znaky (písmena, čísla, pomlčka nebo podtržítko).</div>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Jméno a příjmení <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="jmeno@domena.cz"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Heslo <span class="text-danger">*</span></label>
                            <input
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                required
                            >
                            <div class="form-text">Alespoň 8 znaků.</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Potvrzení hesla <span class="text-danger">*</span></label>
                            <input
                                type="password"
                                class="form-control"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Vytvořit účet</button>
                    </form>

                    <p class="text-center text-muted small mt-3 mb-0">
                        Už máte účet? <a href="{{ route('login') }}">Přihlaste se</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
