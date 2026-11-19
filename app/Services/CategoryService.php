<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    public const MAX_DEPTH = 4;

    public function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'kategorie';
        }

        $slug = $base;
        $counter = 2;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Vrátí zploštěný strom schválených kategorií pro výpis nebo <select>.
     *
     * @return array<int, array{category: Category, depth: int, label: string}>
     */
    public function getApprovedTree(?int $excludeBranchOfId = null): array
    {
        $categories = Category::query()
            ->where('status', 'approved')
            ->orderBy('name')
            ->get();

        $excludedIds = $excludeBranchOfId !== null
            ? $this->collectBranchIds($categories, $excludeBranchOfId)
            : [];

        $byParent = $categories->groupBy(fn (Category $c) => $c->parent_id ?? 0);
        $result = [];

        $walk = function (int $parentId, int $depth) use (&$walk, $byParent, $excludedIds, &$result): void {
            /** @var Collection<int, Category> $children */
            $children = $byParent->get($parentId, collect());
            foreach ($children as $category) {
                if (in_array($category->id, $excludedIds, true)) {
                    continue;
                }

                $prefix = $depth > 1 ? str_repeat('— ', $depth - 1) : '';
                $result[] = [
                    'category' => $category,
                    'depth' => $depth,
                    'label' => $prefix . $category->name,
                ];

                if ($depth < self::MAX_DEPTH) {
                    $walk($category->id, $depth + 1);
                }
            }
        };

        $walk(0, 1);

        return $result;
    }

    /**
     * Vrátí ID dané kategorie a všech jejích schválených podkategorií (pro filtrování událostí).
     *
     * @return array<int, int>
     */
    public function getDescendantIds(int $categoryId): array
    {
        $categories = Category::query()
            ->where('status', 'approved')
            ->get(['id', 'parent_id']);

        return $this->collectBranchIds($categories, $categoryId);
    }

    /**
     * Ověří, že vybraný rodič je platný (schválený, bez cyklu a max. hloubka 4).
     * Vrací českou chybovou hlášku, nebo null pokud je vše v pořádku.
     */
    public function validateParent(?int $parentId, ?Category $editing = null): ?string
    {
        if ($parentId === null) {
            return null;
        }

        if ($editing !== null && $parentId === $editing->id) {
            return 'Kategorie nemůže být nadřazená sama sobě.';
        }

        $parent = Category::find($parentId);
        if ($parent === null || $parent->status !== 'approved') {
            return 'Nadřazená kategorie musí existovat a být schválená.';
        }

        // Kontrola cyklu a výpočet hloubky nadřazeného uzlu
        $parentDepth = 1;
        $cursor = $parent;
        $visited = [$parent->id];

        while ($cursor->parent_id !== null) {
            if ($editing !== null && $cursor->parent_id === $editing->id) {
                return 'Nelze vybrat vlastní podkategorii jako nadřazenou (vznikl by cyklus).';
            }
            if (in_array($cursor->parent_id, $visited, true)) {
                return 'V hierarchii kategorií byl detekován cyklus.';
            }
            $cursor = Category::find($cursor->parent_id);
            if ($cursor === null) {
                break;
            }
            $visited[] = $cursor->id;
            $parentDepth++;
        }

        $subtreeHeight = $editing !== null ? $this->subtreeHeight($editing->id) : 1;

        if (($parentDepth + $subtreeHeight) > self::MAX_DEPTH) {
            return 'Maximální hloubka hierarchie kategorií je ' . self::MAX_DEPTH . ' úrovně.';
        }

        return null;
    }

    /**
     * @param Collection<int, Category> $categories
     * @return array<int, int>
     */
    private function collectBranchIds(Collection $categories, int $rootId): array
    {
        $byParent = $categories->groupBy(fn (Category $c) => $c->parent_id ?? 0);
        $ids = [];
        $queue = [$rootId];

        while ($queue !== []) {
            $current = array_shift($queue);
            if (in_array($current, $ids, true)) {
                continue;
            }
            $ids[] = $current;

            foreach ($byParent->get($current, collect()) as $child) {
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    private function subtreeHeight(int $categoryId): int
    {
        $children = Category::where('parent_id', $categoryId)->pluck('id');
        if ($children->isEmpty()) {
            return 1;
        }

        $maxChildHeight = 0;
        foreach ($children as $childId) {
            $maxChildHeight = max($maxChildHeight, $this->subtreeHeight((int) $childId));
        }

        return 1 + $maxChildHeight;
    }
}
