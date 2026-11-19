<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $categories = Category::query()
            ->with(['parent', 'proposer', 'reviewer'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending' => Category::where('status', 'pending')->count(),
            'approved' => Category::where('status', 'approved')->count(),
            'rejected' => Category::where('status', 'rejected')->count(),
            'all' => Category::count(),
        ];

        return view('moderation.categories.index', [
            'categories' => $categories,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function create(CategoryService $categoryService): View
    {
        $parentOptions = array_filter(
            $categoryService->getApprovedTree(),
            fn (array $item) => $item['depth'] < CategoryService::MAX_DEPTH
        );

        return view('moderation.categories.form', [
            'category' => null,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function store(Request $request, CategoryService $categoryService): RedirectResponse
    {
        $validated = $this->validateCategory($request);
        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        $parentError = $categoryService->validateParent($parentId);
        if ($parentError !== null) {
            return back()->withErrors(['parent_id' => $parentError])->withInput();
        }

        $category = new Category([
            'name' => $validated['name'],
            'slug' => $categoryService->generateUniqueSlug($validated['name']),
            'parent_id' => $parentId,
            'description' => $validated['description'] ?? null,
        ]);
        $category->status = 'approved';
        $category->proposed_by = $request->user()->id;
        $category->reviewed_by = $request->user()->id;
        $category->reviewed_at = now();
        $category->save();

        return redirect()
            ->route('moderation.categories.index', ['status' => 'approved'])
            ->with('success', "Kategorie „{$category->name}“ byla přidána a schválena.");
    }

    public function edit(Category $category, CategoryService $categoryService): View
    {
        $parentOptions = array_filter(
            $categoryService->getApprovedTree($category->id),
            fn (array $item) => $item['depth'] < CategoryService::MAX_DEPTH
        );

        return view('moderation.categories.form', [
            'category' => $category,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function update(Request $request, Category $category, CategoryService $categoryService): RedirectResponse
    {
        $validated = $this->validateCategory($request);
        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        $parentError = $categoryService->validateParent($parentId, $category);
        if ($parentError !== null) {
            return back()->withErrors(['parent_id' => $parentError])->withInput();
        }

        $category->name = $validated['name'];
        $category->slug = $categoryService->generateUniqueSlug($validated['name'], $category->id);
        $category->parent_id = $parentId;
        $category->description = $validated['description'] ?? null;
        $category->save();

        return redirect()
            ->route('moderation.categories.index', ['status' => $category->status])
            ->with('success', "Kategorie „{$category->name}“ byla upravena.");
    }

    public function approve(Request $request, Category $category, CategoryService $categoryService): RedirectResponse
    {
        $parentError = $categoryService->validateParent($category->parent_id, $category);
        if ($parentError !== null) {
            return back()->with('error', $parentError);
        }

        $category->status = 'approved';
        $category->reviewed_by = $request->user()->id;
        $category->reviewed_at = now();
        $category->rejection_reason = null;
        $category->save();

        return back()->with('success', "Kategorie „{$category->name}“ byla schválena.");
    }

    public function reject(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Uveďte důvod zamítnutí kategorie.',
            'rejection_reason.min' => 'Důvod zamítnutí musí mít alespoň :min znaky.',
        ]);

        $category->status = 'rejected';
        $category->reviewed_by = $request->user()->id;
        $category->reviewed_at = now();
        $category->rejection_reason = $validated['rejection_reason'];
        $category->save();

        return back()->with('success', "Kategorie „{$category->name}“ byla zamítnuta.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Kategorii nelze smazat, protože obsahuje podkategorie.');
        }

        if ($category->events()->exists()) {
            return back()->with('error', 'Kategorii nelze smazat, protože je přiřazená k jedné nebo více událostem.');
        }

        $name = $category->name;
        $category->delete();

        return back()->with('success', "Kategorie „{$name}“ byla smazána.");
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Název kategorie je povinný.',
            'parent_id.exists' => 'Vybraná nadřazená kategorie neexistuje.',
        ]);
    }
}
