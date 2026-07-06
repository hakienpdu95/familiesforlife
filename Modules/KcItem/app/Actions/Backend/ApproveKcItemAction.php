<?php

namespace Modules\KcItem\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Models\KcVersionHistory;
use Modules\KcItem\Notifications\KcItemApprovedNotification;

class ApproveKcItemAction
{
    use AsAction;

    public function handle(KcItem $kcItem, ?string $changeSummary = null): KcItem
    {
        $newVersion = $kcItem->version + 1;

        // Tạo snapshot lịch sử phiên bản
        KcVersionHistory::create([
            'uuid'             => Str::uuid(),
            'item_id'          => $kcItem->id,
            'version_number'   => $newVersion,
            'title_snapshot'   => $kcItem->title,
            'content_snapshot' => $kcItem->content ?? '',
            'change_summary'   => $changeSummary,
            'changed_by'       => auth()->id(),
        ]);

        $kcItem->update([
            'status'      => KcItemStatus::Approved->value,
            'version'     => $newVersion,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_by'  => auth()->id(),
        ]);

        // Giữ tối đa 20 versions
        $kcItem->versionHistories()
            ->orderByDesc('version_number')
            ->skip(20)
            ->take(PHP_INT_MAX)
            ->delete();

        // Notify owner if different from approver
        $kcItem->load('owner');
        if ($kcItem->owner && $kcItem->owner->id !== auth()->id()) {
            $kcItem->owner->notify(new KcItemApprovedNotification($kcItem));
        }

        return $kcItem;
    }
}
