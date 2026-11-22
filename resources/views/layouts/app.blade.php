<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Události') – Informační systém</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=4">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-inline-flex align-items-center gap-2 fw-bold text-white me-4" href="{{ route('home') }}">
                <span class="app-brand-mark">U</span>
                <span>Události</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Přepnout navigaci">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto gap-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('events.index', 'events.show') ? 'active' : '' }}" href="{{ route('events.index') }}">
                            Události
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('venues.*') ? 'active' : '' }}" href="{{ route('venues.index') }}">
                            Místa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                            Kategorie
                        </a>
                    </li>
                    @auth
                        @if (auth()->user()->isModerator())
                            <li class="nav-item">
                                <a class="nav-link d-inline-flex align-items-center gap-1 {{ request()->routeIs('moderation.*') ? 'active' : '' }}" href="{{ route('moderation.events.index') }}">
                                    <span>Moderace</span>
                                    @if (($pendingModerationCount ?? 0) > 0)
                                        <span class="badge rounded-pill text-bg-danger" title="Čekajících žádostí ke schválení: {{ $pendingModerationCount }}">
                                            {{ $pendingModerationBadge }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>

                <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                    @auth
                        <a href="{{ route('events.create') }}" class="btn btn-sapling btn-sm px-3">
                            + Nová událost
                        </a>

                        <div class="dropdown">
                            <button
                                class="btn user-menu-btn dropdown-toggle d-inline-flex align-items-center gap-2"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <span class="user-avatar">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="small fw-semibold">{{ auth()->user()->name }}</span>
                                <span class="badge badge-role">{{ auth()->user()->role }}</span>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><h6 class="dropdown-header">Můj přehled</h6></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('calendar.index') }}">Můj kalendář</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('registrations.index') }}">Moje registrace</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('events.mine') }}">Moje pořádané události</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('proposals.index') }}">Moje návrhy míst a kategorií</a>
                                </li>

                                @if (auth()->user()->isAdmin())
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Administrace</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.users.index') }}">Správa uživatelů</a>
                                    </li>
                                @endif

                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">Nastavení profilu</a>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">Odhlásit se</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a class="btn btn-outline-light btn-sm px-3" href="{{ route('login') }}">Přihlásit se</a>
                        <a class="btn btn-sapling btn-sm px-3" href="{{ route('register') }}">Registrace</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zavřít"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zavřít"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-muted small d-flex flex-wrap justify-content-between gap-2">
            <span>Informační systém pro správu událostí</span>
            <span>IIS · FIT VUT</span>
        </div>
    </footer>

    <script src="{{ asset('assets/vendor/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
