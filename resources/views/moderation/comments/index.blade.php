@extends('layouts.app')

@section('title', 'Moderace komentářů')

@section('content')
    @include('moderation._nav')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Moderace komentářů</h1>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'visible' ? 'active' : '' }}" href="{{ route('moderation.comments.index', ['filter' => 'visible']) }}">
                Viditelné <span class="badge text-bg-success ms-1">{{ $counts['visible'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'hidden' ? 'active' : '' }}" href="{{ route('moderation.comments.index', ['filter' => 'hidden']) }}">
                Skryté <span class="badge text-bg-secondary ms-1">{{ $counts['hidden'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('moderation.comments.index', ['filter' => 'all']) }}">
                Vše <span class="badge text-bg-light border text-dark ms-1">{{ $counts['all'] }}</span>
            </a>
        </li>
    </ul>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Událost</th>
                        <th>Autor</th>
                        <th>Komentář</th>
                        <th>Stav</th>
                        <th class="text-end">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comments as $comment)
                        <tr>
                            <td class="small">
                                @if ($comment->event)
                                    <a href="{{ route('events.show', $comment->event->slug) }}" class="text-decoration-none fw-semibold">
                                        {{ $comment->event->title }}
                                    </a>
                                @endif
                            </td>
                            <td class="small text-nowrap">
                                <div>{{ $comment->user?->name ?? '—' }}</div>
                                <div class="text-muted">{{ $comment->created_at->copy()->setTimezone('Europe/Prague')->format('d. m. Y H:i') }}</div>
                            </td>
                            <td class="small">{{ $comment->body }}</td>
                            <td>
                                @if ($comment->is_hidden)
                                    <span class="badge text-bg-secondary">Skryto</span>
                                @else
                                    <span class="badge text-bg-success">Viditelné</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($comment->is_hidden)
                                    <form method="POST" action="{{ route('moderation.comments.show', $comment) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">Zobrazit</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('moderation.comments.hide', $comment) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Skrýt</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">V této záložce nejsou žádné komentáře.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($comments->hasPages())
        <div class="mt-3">
            {{ $comments->links() }}
        </div>
    @endif
@endsection
