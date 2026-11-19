@extends('layouts.app')

@section('title', 'Můj profil')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Můj profil</h1>
        <span class="badge text-bg-secondary">Role: {{ $user->role }}</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <h2 class="h5 mb-3">Osobní údaje</h2>
                    <p class="text-muted small mb-4">Pole označená <span class="text-danger">*</span> jsou povinná.</p>

                    <form method="POST" action="{{ route('profile.update') }}" class="d-flex flex-column flex-grow-1" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="username" class="form-label">Uživatelské jméno <span class="text-muted small">(nelze měnit)</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="username"
                                value="{{ $user->username }}"
                                disabled
                            >
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Jméno a příjmení <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-auto">
                            <button type="submit" class="btn btn-primary">Uložit změny</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <h2 class="h5 mb-3">Změna hesla</h2>
                    <p class="text-muted small mb-4">Pro změnu hesla zadejte nejprve své současné heslo.</p>

                    <form method="POST" action="{{ route('profile.password') }}" class="d-flex flex-column flex-grow-1" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Současné heslo <span class="text-danger">*</span></label>
                            <input
                                type="password"
                                class="form-control @error('current_password', 'passwordUpdate') is-invalid @enderror"
                                id="current_password"
                                name="current_password"
                                required
                            >
                            @error('current_password', 'passwordUpdate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nové heslo <span class="text-danger">*</span> <span class="text-muted small">(alespoň 8 znaků)</span></label>
                            <input
                                type="password"
                                class="form-control @error('password', 'passwordUpdate') is-invalid @enderror"
                                id="password"
                                name="password"
                                required
                            >
                            @error('password', 'passwordUpdate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Potvrzení nového hesla <span class="text-danger">*</span></label>
                            <input
                                type="password"
                                class="form-control"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                            >
                        </div>

                        <div class="mt-auto">
                            <button type="submit" class="btn btn-outline-primary">Změnit heslo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
