<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationCommentController extends Controller
{
    public function index(Request $request): View
    {
        $filter = (string) $request->query('filter', 'visible');
        if (!in_array($filter, ['visible', 'hidden', 'all'], true)) {
            $filter = 'visible';
        }

        $comments = Comment::query()
            ->with(['event', 'user', 'hider'])
            ->when($filter === 'visible', fn ($q) => $q->where('is_hidden', false))
            ->when($filter === 'hidden', fn ($q) => $q->where('is_hidden', true))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'visible' => Comment::where('is_hidden', false)->count(),
            'hidden' => Comment::where('is_hidden', true)->count(),
            'all' => Comment::count(),
        ];

        return view('moderation.comments.index', [
            'comments' => $comments,
            'filter' => $filter,
            'counts' => $counts,
        ]);
    }

    public function hide(Request $request, Comment $comment): RedirectResponse
    {
        $comment->is_hidden = true;
        $comment->hidden_by = $request->user()->id;
        $comment->hidden_at = now();
        $comment->save();

        return back()->with('success', 'Komentář byl skryt.');
    }

    public function show(Comment $comment): RedirectResponse
    {
        $comment->is_hidden = false;
        $comment->hidden_by = null;
        $comment->hidden_at = null;
        $comment->save();

        return back()->with('success', 'Komentář byl znovu zobrazen.');
    }
}
