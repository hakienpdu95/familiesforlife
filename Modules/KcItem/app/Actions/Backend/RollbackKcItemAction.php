<?php

namespace Modules\KcItem\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Models\KcVersionHistory;

class RollbackKcItemAction
{
    use AsAction;

    public function handle(KcItem $kcItem, int $versionNumber): KcItem
    {
        $snapshot = KcVersionHistory::where('item_id', $kcItem->id)
            ->where('version_number', $versionNumber)
            ->firstOrFail();

        $newVersion = $kcItem->version + 1;

        KcVersionHistory::create([
            'uuid'             => Str::uuid(),
            'item_id'          => $kcItem->id,
            'version_number'   => $newVersion,
            'title_snapshot'   => $snapshot->title_snapshot,
            'content_snapshot' => $snapshot->content_snapshot,
            'change_summary'   => "Rolled back to version {$versionNumber}",
            'changed_by'       => auth()->id(),
        ]);

        $kcItem->update([
            'title'      => $snapshot->title_snapshot,
            'content'    => $snapshot->content_snapshot,
            'version'    => $newVersion,
            'status'     => KcItemStatus::Draft->value,
            'updated_by' => auth()->id(),
        ]);

        // Giữ tối đa 20 versions
        $kcItem->versionHistories()
            ->orderByDesc('version_number')
            ->skip(20)
            ->take(PHP_INT_MAX)
            ->delete();

        return $kcItem;
    }
}
