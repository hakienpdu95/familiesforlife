<?php

namespace Modules\Post\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase 12b (spec/PublishingEngine_Technical_Specification.md §3.5) — chuyển dữ liệu
 * post_articles cũ (title/slug/status/...) sang post_article_translations, set
 * translation_id trên post_content_blocks/post_product_blocks. Idempotent (bỏ qua article
 * đã có translation) — chạy lại an toàn nếu lỗi giữa chừng.
 *
 * Quy trình: --dry-run trên staging → soát log → chạy thật (-v để xem chi tiết block/
 * product-block cập nhật từng article) → verify 100% content_blocks/product_blocks có
 * translation_id không null → mới chạy Migration #4 (finalize, Phase 12c).
 */
class BackfillPostTranslationsCommand extends Command
{
    protected $signature = 'post:backfill-translations {--dry-run}';

    protected $description = 'Backfill post_articles (title/slug/status/...) sang post_article_translations';

    private const STATUS_MAP = [
        'draft'          => 'draft',
        'pending_review' => 'submitted',   // đổi tên — KHÔNG copy thẳng chuỗi
        'published'      => 'published',
        'scheduled'      => 'scheduled',
        'archived'       => 'archived',
    ];

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $count  = 0;

        DB::table('post_articles')->whereNull('deleted_at')->orderBy('id')
            ->chunkById(200, function ($articles) use ($dryRun, &$count) {
                foreach ($articles as $a) {
                    // Idempotent: bỏ qua nếu article này đã có translation (cho phép chạy lại
                    // an toàn sau khi sửa lỗi giữa chừng, không tạo trùng).
                    if (DB::table('post_article_translations')->where('article_id', $a->id)->exists()) {
                        continue;
                    }

                    $newStatus   = self::STATUS_MAP[$a->status] ?? 'draft';
                    $isScheduled = $newStatus === 'scheduled';

                    if ($dryRun) {
                        $this->line("[dry-run] article #{$a->id} ({$a->title}) → status={$newStatus}");
                        $count++;

                        continue;
                    }

                    $translationId = DB::table('post_article_translations')->insertGetId([
                        'uuid'            => (string) Str::uuid(),
                        'article_id'      => $a->id,
                        'organization_id' => $a->organization_id,
                        'locale'          => $a->main_locale ?? 'vi',
                        'title'           => $a->title,
                        'slug'            => $a->slug,
                        'excerpt'         => $a->excerpt,
                        'seo_title'       => $a->seo_title,
                        'seo_description' => $a->seo_description,
                        'status'          => $newStatus,
                        // 'scheduled' cũ dùng published_at để lưu ngày DỰ KIẾN (chưa publish
                        // thật) — chuyển vào scheduled_at, không copy thẳng sang published_at.
                        'published_at'    => $isScheduled ? null : $a->published_at,
                        'scheduled_at'    => $isScheduled ? $a->published_at : null,
                        'approved_by'     => $a->approved_by,
                        'approved_at'     => $a->approved_at,
                        'view_count'      => $a->view_count,
                        'created_at'      => $a->created_at,
                        'updated_at'      => $a->updated_at,
                    ]);

                    $cbCount = DB::table('post_content_blocks')->where('article_id', $a->id)->update(['translation_id' => $translationId]);
                    $pbCount = DB::table('post_product_blocks')->where('article_id', $a->id)->update(['translation_id' => $translationId]);

                    if ($this->output->isVerbose()) {
                        $this->line("  article #{$a->id}: {$cbCount} content_blocks, {$pbCount} product_blocks → translation #{$translationId}");
                    }

                    $count++;
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '') . "Đã xử lý {$count} bài viết.");
    }
}
