<?php

namespace Modules\KcItem\Console\Commands;

use Illuminate\Console\Command;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Notifications\KcItemArchivedNotification;
use Modules\KcItem\Notifications\KcItemExpiringSoonNotification;

class ExpireKcItemsCommand extends Command
{
    protected $signature = 'kc:expire-items';

    protected $description = 'Chuyển các tài liệu đã hết hạn (expired_date <= NOW()) sang trạng thái archived.';

    public function handle(): int
    {
        $expired = KcItem::withoutTenant()
            ->where('status', KcItemStatus::Approved->value)
            ->whereNotNull('expired_date')
            ->where('expired_date', '<=', now())
            ->with('owner')
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Không có tài liệu nào cần lưu trữ.');
        } else {
            foreach ($expired as $item) {
                $item->update(['status' => KcItemStatus::Archived->value]);
                $this->line("  [archived] #{$item->id} — {$item->title}");

                // Notify owner
                if ($item->owner) {
                    $item->owner->notify(new KcItemArchivedNotification($item));
                }
            }

            $this->info("Đã lưu trữ {$expired->count()} tài liệu hết hạn.");
        }

        // Cảnh báo sắp hết hạn trong 30 ngày (SOP / Policy)
        $soonExpiringItems = KcItem::withoutTenant()
            ->where('status', KcItemStatus::Approved->value)
            ->whereNotNull('expired_date')
            ->whereBetween('expired_date', [now(), now()->addDays(30)])
            ->whereIn('type', ['sop', 'policy'])
            ->with('owner')
            ->get();

        if ($soonExpiringItems->isNotEmpty()) {
            $this->warn("Có {$soonExpiringItems->count()} tài liệu SOP/Policy sắp hết hạn trong 30 ngày tới.");
            foreach ($soonExpiringItems as $item) {
                if ($item->owner) {
                    $item->owner->notify(new KcItemExpiringSoonNotification($item));
                }
            }
        }

        return self::SUCCESS;
    }
}
