<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a
            class="nav-link d-inline-flex align-items-center gap-1 {{ request()->routeIs('moderation.events.*') ? 'active' : '' }}"
            href="{{ route('moderation.events.index') }}"
        >
            <span>Události</span>
            @if (($pendingEventsCount ?? 0) > 0)
                <span class="badge rounded-pill {{ request()->routeIs('moderation.events.*') ? 'text-bg-light text-dark' : 'text-bg-danger' }}">
                    {{ $pendingEventsCount > 9 ? '9+' : $pendingEventsCount }}
                </span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a
            class="nav-link d-inline-flex align-items-center gap-1 {{ request()->routeIs('moderation.venues.*') ? 'active' : '' }}"
            href="{{ route('moderation.venues.index') }}"
        >
            <span>Místa konání</span>
            @if (($pendingVenuesCount ?? 0) > 0)
                <span class="badge rounded-pill {{ request()->routeIs('moderation.venues.*') ? 'text-bg-light text-dark' : 'text-bg-danger' }}">
                    {{ $pendingVenuesCount > 9 ? '9+' : $pendingVenuesCount }}
                </span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a
            class="nav-link d-inline-flex align-items-center gap-1 {{ request()->routeIs('moderation.categories.*') ? 'active' : '' }}"
            href="{{ route('moderation.categories.index') }}"
        >
            <span>Kategorie</span>
            @if (($pendingCategoriesCount ?? 0) > 0)
                <span class="badge rounded-pill {{ request()->routeIs('moderation.categories.*') ? 'text-bg-light text-dark' : 'text-bg-danger' }}">
                    {{ $pendingCategoriesCount > 9 ? '9+' : $pendingCategoriesCount }}
                </span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a
            class="nav-link {{ request()->routeIs('moderation.comments.*') ? 'active' : '' }}"
            href="{{ route('moderation.comments.index') }}"
        >
            Komentáře
        </a>
    </li>
</ul>
