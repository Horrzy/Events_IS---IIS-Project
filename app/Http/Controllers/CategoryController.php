<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(CategoryService $categoryService): View
    {
        $tree = $categoryService->getApprovedTree();

        return view('categories.index', [
            'tree' => $tree,
        ]);
    }

    public function create(CategoryService $categoryService): View
    {
        $parentOptions = array_filter(
            $categoryService->getApprovedTree(),
            fn (array $item) => $item['depth'] < CategoryService::MAX_DEPTH
        );

        return view('categories.create', [
            'parentOptions' => $parentOptions,
        ]);
    }

    public function store(Request $request, CategoryService $categoryService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Název kategorie je povinný.',
            'parent_id.exists' => 'Vybraná nadřazená kategorie neexistuje.',
        ]);

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        $parentError = $categoryService->validateParent($parentId);

        if ($parentError !== null) {
            return back()
                ->withErrors(['parent_id' => $parentError])
                ->withInput();
        }

        $category = new Category([
            'name' => $validated['name'],
            'slug' => $categoryService->generateUniqueSlug($validated['name']),
            'parent_id' => $parentId,
            'description' => $validated['description'] ?? null,
        ]);
        $category->status = 'pending';
        $category->proposed_by = $request->user()->id;
        $category->save();

        return redirect()
            ->route('proposals.index')
            ->with('success', 'Návrh kategorie byl odeslán ke schválení moderátorem.');
    }
}
