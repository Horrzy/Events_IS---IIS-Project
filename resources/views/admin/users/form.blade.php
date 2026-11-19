@extends('layouts.app')

@section('title', $user ? 'Upravit uživatele' : 'Nový uživatel')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $user ? "Upravit uživatele: {$user->username}" : 'Přidat nového uživatele' }}</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Zpět na seznam</a>
    </div>

    <div class="row g-4">
        <div class="{{ $user ? 'col-lg-6' : 'col-lg-7' }}">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Údaje účtu</h2>
                    <p class="text-muted small mb-4">Pole označená <span class="text-danger">*</span> jsou povinná.</p>

                    <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="username" class="form-label">
                                Uživatelské jméno
                                @if (!$user)
                                    <span class="text-danger">*</span>
                                @else
                                    <span class="text-muted small">(nelze měnit)</span>
                                @endif
                            </label>
                            <input
                                type="text"
                                class="form-control @error('username') is-invalid @enderror"
                                id="username"
                                name="username"
                                value="{{ old('username', $user?->username) }}"
                                @disabled((bool) $user)
                                @required(!$user)
                            >
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
                                value="{{ old('name', $user?->name) }}"
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
                                value="{{ old('email', $user?->email) }}"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                @foreach ($roles as $key => $label)
                                    <option value="{{ $key }}" @selected(old('role', $user?->role ?? 'user') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if (!$user)
                            <div class="mb-3">
                                <label for="password" class="form-label">Heslo <span class="text-danger">*</span> <span class="text-muted small">(alespoň 8 znaků)</span></label>
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

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Potvrzení hesla <span class="text-danger">*</span></label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                >
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                                <label class="form-check-label" for="is_active">Účet je aktivní</label>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">{{ $user ? 'Uložit změny' : 'Vytvořit uživatele' }}</button>
                    </form>
                </div>
            </div>
        </div>

        @if ($user)
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Reset hesla</h2>
                        <p class="text-muted small mb-4">Nastavte uživateli nové heslo.</p>

                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="reset_password" class="form-label">Nové heslo <span class="text-danger">*</span> <span class="text-muted small">(alespoň 8 znaků)</span></label>
                                <input
                                    type="password"
                                    class="form-control @error('password', 'passwordReset') is-invalid @enderror"
                                    id="reset_password"
                                    name="password"
                                    required
                                >
                                @error('password', 'passwordReset')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="reset_password_confirmation" class="form-label">Potvrzení hesla <span class="text-danger">*</span></label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="reset_password_confirmation"
                                    name="password_confirmation"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-outline-primary">Nastavit nové heslo</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
