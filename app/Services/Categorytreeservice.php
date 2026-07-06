<?php

namespace App\Services;

use App\Models\Category;

class CategoryTreeService
{
    /**
     * Return every descendant id of the given category id.
     *
     * The original implementation ran one query per tree level
     * (findOrFail -> query children -> query grandchildren -> ...).
     * This pulls the whole (small) categories table once and walks
     * an in-memory parent -> children map instead, so it's a single
     * query no matter how deep the tree is.
     */
    public function descendantIds(int $id): array
    {
        $childrenMap = [];

        Category::select('id', 'parent_id')
            ->get()
            ->each(function ($category) use (&$childrenMap) {
                $childrenMap[$category->parent_id][] = $category->id;
            });

        $descendants = [];
        $stack = $childrenMap[$id] ?? [];

        while (!empty($stack)) {
            $current = array_pop($stack);
            $descendants[] = $current;

            foreach ($childrenMap[$current] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return $descendants;
    }
}
