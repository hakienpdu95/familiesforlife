<?php

namespace Modules\Branch\Observers;

use Modules\Branch\Models\Branch;

/**
 * BranchObserver — quản lý materialized path (path + depth) cho cây branch.
 *
 * Path format: /{id1}/{id2}/{self_id}/
 * Ví dụ: root id=1 → path=/1/, child id=5 → path=/1/5/, grandchild id=12 → path=/1/5/12/
 *
 * Khi đổi parent_id: cập nhật path/depth cho self và toàn bộ descendants.
 */
class BranchObserver
{
    public function created(Branch $branch): void
    {
        $parent = $branch->parent_id
            ? Branch::withoutTenant()->withTrashed()->find($branch->parent_id)
            : null;

        $path  = $parent ? ($parent->path . $branch->id . '/') : ('/' . $branch->id . '/');
        $depth = $parent ? ($parent->depth + 1) : 0;

        Branch::withoutTenant()
            ->where('id', $branch->id)
            ->update(['path' => $path, 'depth' => $depth]);
    }

    public function updating(Branch $branch): void
    {
        if (! $branch->isDirty('parent_id')) {
            return;
        }

        $newParent = $branch->parent_id
            ? Branch::withoutTenant()->withTrashed()->find($branch->parent_id)
            : null;

        $branch->path  = $newParent ? ($newParent->path . $branch->id . '/') : ('/' . $branch->id . '/');
        $branch->depth = $newParent ? ($newParent->depth + 1) : 0;
    }

    public function updated(Branch $branch): void
    {
        if (! $branch->wasChanged('path')) {
            return;
        }

        $oldPath = $branch->getOriginal('path');
        $newPath = $branch->path;

        // Cập nhật path cho toàn bộ descendants
        Branch::withoutTenant()
            ->where('path', 'like', $oldPath . '%')
            ->where('id', '!=', $branch->id)
            ->chunkById(100, function ($descendants) use ($oldPath, $newPath): void {
                foreach ($descendants as $descendant) {
                    $updatedPath  = str_replace($oldPath, $newPath, $descendant->path);
                    $updatedDepth = substr_count($updatedPath, '/') - 1;
                    Branch::withoutTenant()
                        ->where('id', $descendant->id)
                        ->update(['path' => $updatedPath, 'depth' => $updatedDepth]);
                }
            });
    }
}
