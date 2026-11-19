<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Události') – Informační systém</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('home') }}">Události</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Přepnout navigaci">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">Úvod</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('events.index') }}">Události</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('venues.index') }}">Místa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('categories.index') }}">Kategorie</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('calendar.index') }}">Kalendář</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('registrations.index') }}">Registrace</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('events.mine') }}">Moje události</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('proposals.index') }}">Moje návrhy</a>
                        </li>
                        @if (auth()->user()->isModerator())
                            <li class="nav-item">
                                <a class="nav-link d-inline-flex align-items-center gap-1" href="{{ route('moderation.events.index') }}">
                                    <span>Moderace</span>
                                    @if (($pendingModerationCount ?? 0) > 0)
                                        <span class="badge rounded-pill text-bg-danger" title="Čekajících žádostí ke schválení: {{ $pendingModerationCount }}">
                                            {{ $pendingModerationBadge }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.users.index') }}">Správa uživatelů</a>
                            </li>
                        @endif
                    @endauth
                </ul>
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link text-light" href="{{ route('profile.edit') }}">
                                {{ auth()->user()->name }}
                                <span class="badge text-bg-secondary ms-1">{{ auth()->user()->role }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-light btn-sm">Odhlásit se</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Přihlásit se</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Registrace</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zavřít"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zavřít"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-muted small d-flex justify-content-between">
            <span>Informační systém pro správu událostí</span>
            <span>IIS · FIT VUT</span>
        </div>
    </footer>

    <script src="{{ asset('assets/vendor/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
