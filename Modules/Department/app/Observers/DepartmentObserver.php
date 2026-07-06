<?php

namespace Modules\Department\Observers;

use Modules\Department\Models\Department;

/**
 * DepartmentObserver — quản lý materialized path (path + depth) cho cây department.
 *
 * Path format: /{id1}/{id2}/{self_id}/
 * Khi đổi parent_id: cập nhật path/depth cho self và toàn bộ descendants.
 */
class DepartmentObserver
{
    public function created(Department $dept): void
    {
        $parent = $dept->parent_id
            ? Department::withoutTenant()->withTrashed()->find($dept->parent_id)
            : null;

        $path  = $parent ? ($parent->path . $dept->id . '/') : ('/' . $dept->id . '/');
        $depth = $parent ? ($parent->depth + 1) : 0;

        Department::withoutTenant()
            ->where('id', $dept->id)
            ->update(['path' => $path, 'depth' => $depth]);
    }

    public function updating(Department $dept): void
    {
        if (! $dept->isDirty('parent_id')) {
            return;
        }

        $newParent = $dept->parent_id
            ? Department::withoutTenant()->withTrashed()->find($dept->parent_id)
            : null;

        $dept->path  = $newParent ? ($newParent->path . $dept->id . '/') : ('/' . $dept->id . '/');
        $dept->depth = $newParent ? ($newParent->depth + 1) : 0;
    }

    public function updated(Department $dept): void
    {
        if (! $dept->wasChanged('path')) {
            return;
        }

        $oldPath = $dept->getOriginal('path');
        $newPath = $dept->path;

        Department::withoutTenant()
            ->where('path', 'like', $oldPath . '%')
            ->where('id', '!=', $dept->id)
            ->chunkById(100, function ($descendants) use ($oldPath, $newPath): void {
                foreach ($descendants as $descendant) {
                    $updatedPath  = str_replace($oldPath, $newPath, $descendant->path);
                    $updatedDepth = substr_count($updatedPath, '/') - 1;
                    Department::withoutTenant()
                        ->where('id', $descendant->id)
                        ->update(['path' => $updatedPath, 'depth' => $updatedDepth]);
                }
            });
    }
}
