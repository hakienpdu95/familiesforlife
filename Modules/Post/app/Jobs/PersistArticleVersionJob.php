<?php

namespace Modules\Post\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Features\VersionHistory\Actions\PruneVersionsAction;
use Modules\Post\Models\PostArticleVersion;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §9.3 — hash + khoá số thứ tự + insert
 * chạy bất đồng bộ. Snapshot đã "đóng băng" ở CreateArticleVersionAction — job KHÔNG đọc lại
 * state hiện tại của translation, nên không có race điều kiện dù chạy trễ (§9.1/§9.4).
 */
class PersistArticleVersionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $translationId,
        private readonly VersionTrigger $trigger,
        private readonly ?int $userId,
        private readonly array $snapshot,
        private readonly string $titleSnapshot,
        private readonly ?int $restoredFromVersionId = null,
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $hash = hash('sha256', json_encode($this->snapshot));

            $latest = PostArticleVersion::where('translation_id', $this->translationId)
                ->lockForUpdate()
                ->orderByDesc('version_number')
                ->first();

            // Bỏ qua nếu nội dung y hệt version gần nhất VÀ đây là 1 lần "save" thường —
            // tránh version rác khi editor bấm Cập nhật mà không đổi gì. Publish/Restore
            // LUÔN ghi (đánh dấu 1 mốc lifecycle quan trọng dù trùng nội dung).
            if ($this->trigger === VersionTrigger::Save && $latest?->content_hash === $hash) {
                return;
            }

            PostArticleVersion::create([
                'translation_id'  => $this->translationId,
                'version_number'  => ($latest?->version_number ?? 0) + 1,
                'trigger'         => $this->trigger,
                'snapshot'        => $this->snapshot,
                'title_snapshot'  => $this->titleSnapshot,
                'content_hash'    => $hash,
                'char_count'      => $this->charCount(),
                'block_count'     => count($this->snapshot['blocks']),
                'restored_from_version_id' => $this->restoredFromVersionId,
                'created_by'      => $this->userId,
            ]);
        });

        app(PruneVersionsAction::class)->handle($this->translationId); // §10 — no-op nếu chưa cấu hình giới hạn
    }

    /**
     * §9.4 — tính cả "trọng lượng" product block (không chỉ text), vì đổi sản phẩm/nút
     * trong 1 khối không nhất thiết đổi độ dài text nào nhưng vẫn là 1 thay đổi nội dung đáng
     * kể cần phản ánh ở chỉ số hiển thị "+N/-M".
     */
    private function charCount(): int
    {
        return array_sum(array_map(function ($b) {
            if ($b['type'] === 'text') {
                return mb_strlen(strip_tags($b['text_html']));
            }

            $itemWeight   = count($b['items']) * 50;
            $buttonWeight = (array_sum(array_map(fn ($i) => count($i['buttons']), $b['items']))
                + count($b['block_buttons'])) * 10;

            return $itemWeight + $buttonWeight;
        }, $this->snapshot['blocks']));
    }
}
