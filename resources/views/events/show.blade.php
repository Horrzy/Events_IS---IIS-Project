@extends('layouts.app')

@section('title', $event->title)

@section('content')
    @php
        $startPrague = $event->starts_at->copy()->setTimezone('Europe/Prague');
        $endPrague = $event->ends_at->copy()->setTimezone('Europe/Prague');
        $isPast = $event->ends_at->isPast();
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge text-bg-secondary">{{ $event->code() }}</span>
                <span class="badge text-bg-light border text-dark">{{ $event->category?->name }}</span>
                @if ($isPast)
                    <span class="badge text-bg-dark">Proběhlá událost</span>
                @endif
            </div>
            <h1 class="h2 mb-0">{{ $event->title }}</h1>
        </div>
        <div class="d-flex gap-2">
            @auth
                @if ($event->manager_id === auth()->id() || auth()->user()->isAdmin())
                    <a href="{{ route('registrations.participants', $event) }}" class="btn btn-outline-secondary">
                        Účastníci ({{ $occupiedCount }})
                    </a>
                    @if ($event->status !== 'cancelled')
                        <a href="{{ route('events.edit', $event) }}" class="btn btn-outline-primary">Upravit událost</a>
                    @endif
                @endif
            @endauth
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary">Zpět na události</a>
        </div>
    </div>

    @if ($event->status === 'draft')
        <div class="alert alert-secondary">Tato událost je zatím uložena jako <strong>koncept</strong> a není veřejná.</div>
    @elseif ($event->status === 'pending')
        <div class="alert alert-warning">Tato událost <strong>čeká na schválení moderátorem</strong>.</div>
    @elseif ($event->status === 'rejected')
        <div class="alert alert-danger">
            <strong>Událost byla zamítnuta.</strong>
            @if ($event->rejection_reason)
                <div class="mt-1">Důvod: {{ $event->rejection_reason }}</div>
            @endif
        </div>
    @elseif ($event->status === 'cancelled')
        <div class="alert alert-danger">
            <strong>Událost byla zrušena.</strong>
            @if ($event->cancelled_reason)
                <div class="mt-1">Důvod: {{ $event->cancelled_reason }}</div>
            @endif
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                @if ($event->cover_path)
                    <img
                        src="{{ asset($event->cover_path) }}"
                        class="card-img-top object-fit-cover"
                        alt="{{ $event->title }}"
                        style="max-height: 360px;"
                    >
                @endif
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">O události</h2>
                    <div class="mb-0" style="white-space: pre-line;">{{ $event->description }}</div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Typy vstupného</h2>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kategorie vstupného</th>
                                <th>Cena</th>
                                <th>Kapacita typu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($event->ticketTypes as $ticket)
                                <tr>
                                    <td class="fw-semibold">{{ $ticket->name }}</td>
                                    <td>
                                        @if ((float) $ticket->price === 0.0)
                                            <span class="badge text-bg-success">Zdarma</span>
                                        @else
                                            {{ number_format((float) $ticket->price, 2, ',', ' ') }} Kč
                                        @endif
                                    </td>
                                    <td class="text-muted small">
                                        {{ $ticket->capacity !== null ? $ticket->capacity . ' míst' : 'Dle celkové kapacity' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($event->payment_note)
                    <div class="card-footer bg-light small">
                        <strong>Pokyny k platbě:</strong> {{ $event->payment_note }}
                    </div>
                @endif
            </div>

            @if ($event->status === 'approved')
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h2 class="h5 mb-0">Hodnocení a komentáře</h2>
                        <span class="small text-muted">
                            @if ($averageRating !== null)
                                Průměrné hodnocení: <strong>{{ number_format($averageRating, 1, ',', ' ') }} / 5</strong> ({{ $ratingsCount }}×)
                            @else
                                Zatím bez hodnocení
                            @endif
                        </span>
                    </div>
                    <div class="card-body p-4">
                        @if ($canGiveFeedback)
                            <div class="border rounded p-3 bg-light mb-4">
                                <form method="POST" action="{{ route('events.ratings.store', $event->slug) }}" class="mb-3">
                                    @csrf
                                    <label for="stars" class="form-label small fw-semibold">Vaše hodnocení (1–5 hvězdiček)</label>
                                    <div class="d-flex gap-2 align-items-center">
                                        <select class="form-select form-select-sm" style="max-width: 220px;" id="stars" name="stars" required>
                                            @for ($s = 5; $s >= 1; $s--)
                                                <option value="{{ $s }}" @selected((int) old('stars', $userRating?->stars ?? 5) === $s)>
                                                    {{ $s }} / 5 {{ str_repeat('★', $s) }}
                                                </option>
                                            @endfor
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            {{ $userRating ? 'Upravit hodnocení' : 'Ohodnotit' }}
                                        </button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('events.comments.store', $event->slug) }}" novalidate>
                                    @csrf
                                    <label for="body" class="form-label small fw-semibold">Přidat komentář</label>
                                    <textarea
                                        class="form-control form-control-sm mb-2 @error('body') is-invalid @enderror"
                                        id="body"
                                        name="body"
                                        rows="3"
                                        placeholder="Napište, jak se vám událost líbila..."
                                        required
                                    >{{ old('body') }}</textarea>
                                    @error('body')
                                        <div class="invalid-feedback mb-2">{{ $message }}</div>
                                    @enderror
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Odeslat komentář</button>
                                </form>
                            </div>
                        @elseif (!$isPast)
                            <p class="small text-muted mb-3">Hodnotit a komentovat bude možné po skončení události (pro potvrzené účastníky).</p>
                        @elseif (!auth()->check())
                            <p class="small text-muted mb-3">Pro přidání hodnocení nebo komentáře se přihlaste (pouze pro potvrzené účastníky).</p>
                        @else
                            <p class="small text-muted mb-3">Hodnotit a komentovat mohou pouze účastníci s potvrzenou registrací na tuto událost.</p>
                        @endif

                        @forelse ($comments as $comment)
                            <div class="border-top pt-3 mt-3 {{ $comment->is_hidden ? 'opacity-75' : '' }}">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <div>
                                        <span class="fw-semibold small">{{ $comment->user?->name ?? 'Uživatel' }}</span>
                                        <span class="text-muted small ms-2">
                                            {{ $comment->created_at->copy()->setTimezone('Europe/Prague')->format('d. m. Y H:i') }}
                                        </span>
                                        @if ($comment->is_hidden)
                                            <span class="badge text-bg-secondary ms-1">Skryto moderátorem</span>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-1">
                                        @auth
                                            @if (auth()->user()->isModerator())
                                                @if ($comment->is_hidden)
                                                    <form method="POST" action="{{ route('moderation.comments.show', $comment) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-link text-success p-0 text-decoration-none">Zobrazit</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('moderation.comments.hide', $comment) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none">Skrýt</button>
                                                    </form>
                                                @endif
                                            @endif
                                            @if ($comment->user_id === auth()->id())
                                                <form method="POST" action="{{ route('comments.destroy', $comment) }}" onsubmit="return confirm('Opravdu chcete smazat svůj komentář?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-link text-secondary p-0 ms-2 text-decoration-none">Smazat</button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                                <div class="small" style="white-space: pre-line;">{{ $comment->body }}</div>
                            </div>
                        @empty
                            <p class="small text-muted mb-0">Zatím nebyly přidány žádné komentáře.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Praktické informace</h2>
                    <dl class="mb-0">
                        <dt class="small text-muted">Začátek</dt>
                        <dd>{{ $startPrague->format('d. m. Y H:i') }}</dd>

                        <dt class="small text-muted">Konec</dt>
                        <dd>{{ $endPrague->format('d. m. Y H:i') }}</dd>

                        <dt class="small text-muted">Místo konání</dt>
                        <dd>
                            @if ($event->venue)
                                <a href="{{ route('venues.show', $event->venue) }}" class="text-decoration-none fw-semibold">
                                    {{ $event->venue->name }}
                                </a>
                                <div class="small text-muted">{{ $event->venue->street }}, {{ $event->venue->city }}</div>
                            @endif
                        </dd>

                        <dt class="small text-muted">Kapacita</dt>
                        <dd>
                            @if ($event->capacity === null)
                                Neomezená (přihlášeno: {{ $occupiedCount }})
                            @else
                                {{ $occupiedCount }} / {{ $event->capacity }} míst
                            @endif
                        </dd>

                        <dt class="small text-muted">Pořadatel</dt>
                        <dd class="mb-0">{{ $event->manager?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($event->status === 'approved')
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Registrace na událost</h2>

                        @if ($isPast)
                            <p class="small text-muted mb-0">Událost již proběhla, registrace je uzavřena.</p>
                        @elseif (!auth()->check())
                            <p class="small text-muted mb-3">Pro registraci na událost se nejprve přihlaste.</p>
                            <a href="{{ route('login') }}" class="btn btn-primary w-100">Přihlásit se</a>
                        @elseif ($userRegistration)
                            @if ($userRegistration->status === 'confirmed')
                                <div class="alert alert-success py-2 small mb-3">
                                    <strong>Jste registrováni (potvrzeno)</strong><br>
                                    Vstupné: {{ $userRegistration->ticketType?->name }}
                                    ({{ (float) $userRegistration->unit_price === 0.0 ? 'Zdarma' : number_format((float) $userRegistration->unit_price, 2, ',', ' ') . ' Kč' }})
                                </div>
                            @else
                                <div class="alert alert-warning py-2 small mb-3">
                                    <strong>Registrace čeká na potvrzení úhrady</strong><br>
                                    Vstupné: {{ $userRegistration->ticketType?->name }}
                                    ({{ number_format((float) $userRegistration->unit_price, 2, ',', ' ') }} Kč)
                                    @if ($userRegistration->payment_claimed_at)
                                        <div class="mt-1 text-success">Úhradu jste již nahlásili.</div>
                                    @endif
                                </div>

                                @if (!$userRegistration->payment_claimed_at)
                                    <form method="POST" action="{{ route('registrations.claim-payment', $userRegistration) }}" class="mb-2">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm w-100">Uhradil jsem vstupné</button>
                                    </form>
                                @endif
                            @endif

                            @if ($event->starts_at->isFuture())
                                <form method="POST" action="{{ route('registrations.cancel', $userRegistration) }}" onsubmit="return confirm('Opravdu chcete zrušit registraci?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Zrušit registraci</button>
                                </form>
                            @endif
                        @elseif ($event->capacity !== null && $occupiedCount >= $event->capacity)
                            <div class="alert alert-secondary py-2 small mb-0">Kapacita události je již plná.</div>
                        @else
                            <form method="POST" action="{{ route('registrations.store', $event->slug) }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="ticket_type_id" class="form-label small">Vyberte typ vstupného <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="ticket_type_id" name="ticket_type_id" required>
                                        @foreach ($event->ticketTypes as $ticket)
                                            @php
                                                $tOccupied = (int) ($ticketOccupiedCounts[$ticket->id] ?? 0);
                                                $tFull = $ticket->capacity !== null && $tOccupied >= $ticket->capacity;
                                            @endphp
                                            <option value="{{ $ticket->id }}" @disabled($tFull)>
                                                {{ $ticket->name }} —
                                                {{ (float) $ticket->price === 0.0 ? 'Zdarma' : number_format((float) $ticket->price, 0, ',', ' ') . ' Kč' }}
                                                @if ($tFull) (vyprodáno) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Registrovat se</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a
                        href="{{ $googleCalendarUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-outline-primary"
                    >
                        Přidat do Google kalendáře
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
