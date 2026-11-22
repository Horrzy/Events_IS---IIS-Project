@extends('layouts.app')

@section('title', 'Správa uživatelů')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Správa uživatelů</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Přidat uživatele</a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="q" class="form-label small text-muted mb-1">Hledat</label>
                    <input type="text" class="form-control" id="q" name="q" value="{{ $search }}" placeholder="Jméno, login nebo e-mail">
                </div>
                <div class="col-md-4">
                    <label for="role" class="form-label small text-muted mb-1">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">Všechny role</option>
                        @foreach ($roles as $key => $label)
                            <option value="{{ $key }}" @selected($selectedRole === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">Filtrovat</button>
                    @if ($search !== '' || $selectedRole !== '')
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Zrušit</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Login</th>
                        <th>Jméno</th>
                        <th>E-mail</th>
                        <th>Role</th>
                        <th>Stav</th>
                        <th class="text-end">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->username }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->role === 'admin' ? 'text-bg-danger' : ($user->role === 'moderator' ? 'text-bg-warning' : 'text-bg-secondary') }}">
                                    {{ $roles[$user->role] ?? $user->role }}
                                </span>
                            </td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge text-bg-success">Aktivní</span>
                                @else
                                    <span class="badge text-bg-secondary">Deaktivovaný</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Upravit</a>

                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $user->is_active ? 'Deaktivovat' : 'Aktivovat' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Opravdu chcete tohoto uživatele smazat?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Smazat</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nebyli nalezeni žádní uživatelé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($users->hasPages())
        <div class="mt-3">
            {{ $users->links() }}
        </div>
    @endif
@endsection
