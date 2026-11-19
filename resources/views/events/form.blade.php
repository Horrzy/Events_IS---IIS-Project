@extends('layouts.app')

@section('title', $event ? 'Upravit událost' : 'Nová událost')

@section('content')
    @php
        $oldTickets = old('tickets');
        if ($oldTickets === null) {
            if ($event && $event->ticketTypes->isNotEmpty()) {
                $oldTickets = $event->ticketTypes->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'price' => (string) $t->price,
                    'capacity' => $t->capacity !== null ? (string) $t->capacity : '',
                ])->all();
            } else {
                $oldTickets = [
                    ['id' => '', 'name' => 'Základní vstupné', 'price' => '0', 'capacity' => ''],
                ];
            }
        }
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $event ? "Upravit událost: {$event->title}" : 'Vytvořit novou událost' }}</h1>
        <a href="{{ route('events.mine') }}" class="btn btn-outline-secondary">Zpět na moje události</a>
    </div>

    @if ($venues->isEmpty() || $categoryTree === [])
        <div class="alert alert-warning">
            Pro vytvoření události musí v systému existovat alespoň jedno schválené místo konání a jedna schválená kategorie.
        </div>
    @endif

    <form
        method="POST"
        action="{{ $event ? route('events.update', $event) : route('events.store') }}"
        enctype="multipart/form-data"
        novalidate
    >
        @csrf

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Základní údaje</h2>
                <p class="text-muted small mb-4">Pole označená <span class="text-danger">*</span> jsou povinná. Časy zadávejte v českém čase (Europe/Prague).</p>

                <div class="mb-3">
                    <label for="title" class="form-label">Název události <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control @error('title') is-invalid @enderror"
                        id="title"
                        name="title"
                        value="{{ old('title', $event?->title) }}"
                        placeholder="Např. Podzimní PHP meetup"
                        required
                    >
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="venue_id" class="form-label">Místo konání <span class="text-danger">*</span></label>
                        <select class="form-select @error('venue_id') is-invalid @enderror" id="venue_id" name="venue_id" required>
                            <option value="">— Vyberte schválené místo —</option>
                            @foreach ($venues as $venue)
                                <option value="{{ $venue->id }}" @selected((string) old('venue_id', $event?->venue_id) === (string) $venue->id)>
                                    {{ $venue->name }} ({{ $venue->city }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Chybí vaše místo? <a href="{{ route('venues.create') }}">Navrhněte nové místo</a>.
                        </div>
                        @error('venue_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Kategorie <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                            <option value="">— Vyberte kategorii —</option>
                            @foreach ($categoryTree as $item)
                                <option value="{{ $item['category']->id }}" @selected((string) old('category_id', $event?->category_id) === (string) $item['category']->id)>
                                    {{ $item['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Chybí kategorie? <a href="{{ route('categories.create') }}">Navrhněte novou kategorii</a>.
                        </div>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="starts_at" class="form-label">Začátek <span class="text-danger">*</span></label>
                        <input
                            type="datetime-local"
                            class="form-control @error('starts_at') is-invalid @enderror"
                            id="starts_at"
                            name="starts_at"
                            value="{{ old('starts_at', $defaultStart) }}"
                            required
                        >
                        @error('starts_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="ends_at" class="form-label">Konec <span class="text-danger">*</span></label>
                        <input
                            type="datetime-local"
                            class="form-control @error('ends_at') is-invalid @enderror"
                            id="ends_at"
                            name="ends_at"
                            value="{{ old('ends_at', $defaultEnd) }}"
                            required
                        >
                        @error('ends_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="capacity" class="form-label">Celková kapacita <span class="text-muted small">(nepovinné)</span></label>
                        <input
                            type="number"
                            min="1"
                            class="form-control @error('capacity') is-invalid @enderror"
                            id="capacity"
                            name="capacity"
                            value="{{ old('capacity', $event?->capacity) }}"
                            placeholder="Prázdné = neomezená"
                        >
                        @error('capacity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Popis události <span class="text-danger">*</span></label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="5"
                        required
                    >{{ old('description', $event?->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="payment_note" class="form-label">Pokyny k platbě <span class="text-muted small">(nepovinné – např. číslo účtu u placených akcí)</span></label>
                    <textarea
                        class="form-control @error('payment_note') is-invalid @enderror"
                        id="payment_note"
                        name="payment_note"
                        rows="2"
                        placeholder="Např. Číslo účtu 123456/0100, do poznámky uveďte své uživatelské jméno."
                    >{{ old('payment_note', $event?->payment_note) }}</textarea>
                    @error('payment_note')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if ($event?->cover_path)
                    <div class="mb-3">
                        <div class="mb-2">
                            <img src="{{ asset($event->cover_path) }}" alt="{{ $event->title }}" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remove_cover" name="remove_cover" value="1">
                            <label class="form-check-label" for="remove_cover">Odebrat stávající titulní obrázek</label>
                        </div>
                    </div>
                @endif

                <div class="mb-0">
                    <label for="cover" class="form-label">Titulní fotka <span class="text-muted small">(nepovinné – JPG, PNG, WebP, max 2 MB)</span></label>
                    <input
                        type="file"
                        class="form-control @error('cover') is-invalid @enderror"
                        id="cover"
                        name="cover"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    @error('cover')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 mb-0">Typy vstupného <span class="text-danger">*</span></h2>
                        <div class="small text-muted">Zadejte alespoň jeden typ (cena 0 = vstup zdarma).</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-ticket-btn">+ Přidat typ vstupného</button>
                </div>

                @error('tickets')
                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
                @enderror

                <div id="tickets-container" class="d-flex flex-column gap-2">
                    @foreach ($oldTickets as $index => $ticket)
                        <div class="row g-2 align-items-start ticket-row border rounded p-2 bg-light">
                            <input type="hidden" name="tickets[{{ $index }}][id]" value="{{ $ticket['id'] ?? '' }}">
                            <div class="col-md-5">
                                <label class="form-label small mb-1">Název vstupenky <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control form-control-sm @error("tickets.{$index}.name") is-invalid @enderror"
                                    name="tickets[{{ $index }}][name]"
                                    value="{{ $ticket['name'] ?? '' }}"
                                    placeholder="Např. Základní / Student / VIP"
                                    required
                                >
                                @error("tickets.{$index}.name")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Cena (Kč) <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control form-control-sm @error("tickets.{$index}.price") is-invalid @enderror"
                                    name="tickets[{{ $index }}][price]"
                                    value="{{ $ticket['price'] ?? '0' }}"
                                    required
                                >
                                @error("tickets.{$index}.price")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Kapacita typu (nepovinné)</label>
                                <input
                                    type="number"
                                    min="1"
                                    class="form-control form-control-sm @error("tickets.{$index}.capacity") is-invalid @enderror"
                                    name="tickets[{{ $index }}][capacity]"
                                    value="{{ $ticket['capacity'] ?? '' }}"
                                    placeholder="Bez omezení"
                                >
                                @error("tickets.{$index}.capacity")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-1 d-flex align-items-end pt-4">
                                <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-ticket-btn" title="Odebrat">&times;</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if (!$event || in_array($event->status, ['draft', 'rejected'], true))
                <button type="submit" name="action" value="submit" class="btn btn-primary">
                    Uložit a odeslat ke schválení
                </button>
                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                    Uložit jako koncept
                </button>
            @else
                <button type="submit" name="action" value="save" class="btn btn-primary">
                    Uložit změny
                </button>
            @endif
        </div>
    </form>

    <script>
        (function () {
            const container = document.getElementById('tickets-container');
            const addBtn = document.getElementById('add-ticket-btn');
            let nextIndex = container.querySelectorAll('.ticket-row').length;

            addBtn.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'row g-2 align-items-start ticket-row border rounded p-2 bg-light';
                row.innerHTML = `
                    <input type="hidden" name="tickets[${nextIndex}][id]" value="">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Název vstupenky <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="tickets[${nextIndex}][name]" placeholder="Např. VIP / Student" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Cena (Kč) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="tickets[${nextIndex}][price]" value="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Kapacita typu (nepovinné)</label>
                        <input type="number" min="1" class="form-control form-control-sm" name="tickets[${nextIndex}][capacity]" placeholder="Bez omezení">
                    </div>
                    <div class="col-md-1 d-flex align-items-end pt-4">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-ticket-btn" title="Odebrat">&times;</button>
                    </div>
                `;
                container.appendChild(row);
                nextIndex++;
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-ticket-btn')) {
                    const rows = container.querySelectorAll('.ticket-row');
                    if (rows.length > 1) {
                        e.target.closest('.ticket-row').remove();
                    }
                }
            });
        })();
    </script>
@endsection
