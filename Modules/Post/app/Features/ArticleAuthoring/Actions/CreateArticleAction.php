<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use App\Services\Media\MediaUploadService;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\SyncsArticleRelations;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Models\PostArticle;

/** Chỉ tạo "vỏ" PostArticle (chưa có translation nào) — nội dung/title tạo qua CreateTranslationAction ngay sau, trên trang edit. */
class CreateArticleAction
{
    use AsAction;
    use SyncsArticleRelations;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    public function handle(ArticleData $data): PostArticle
    {
        return DB::transaction(function () use ($data) {
            $article = PostArticle::create([
                'main_locale'            => $data->main_locale ?: config('post.default_locale'),
                'format'                 => $data->format,
                'redirect_url'           => $data->format === ArticleFormat::Redirect ? $data->redirect_url : null,
                'is_featured'            => $data->is_featured,
                'province_code'          => $data->province_code,
                'ward_code'              => $data->ward_code,
                'created_by'             => auth()->id(),
                // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §7.1 — khi is_sponsored=false, mọi
                // field sponsor phải NULL (kể cả nếu request gửi kèm rác do UI/JS lỗi).
                'is_sponsored'           => $data->is_sponsored,
                'sponsor_name'           => $data->is_sponsored ? $data->sponsor_name : null,
                'sponsor_logo_url'       => $data->is_sponsored ? $data->sponsor_logo_url : null,
                'sponsor_label'          => $data->is_sponsored ? $data->sponsor_label : null,
                'campaign_code'          => $data->is_sponsored ? $data->campaign_code : null,
                'sponsored_start_date'   => $data->is_sponsored ? $data->sponsored_start_date : null,
                'sponsored_end_date'     => $data->is_sponsored ? $data->sponsored_end_date : null,
            ]);

            $this->syncCategories($article, $data);
            $this->syncTags($article, $data);

            // spec/Media_Library_Technical_Specification.md §8 — form tạo mới chưa có article.id
            // lúc FilePond upload, ảnh cover tạm gắn ở FilePondDraft — "nhận" vào article thật
            // vừa tạo ngay đây.
            if ($data->cover_media_uuid) {
                $this->mediaUpload->reassociateFilePondDrafts($article, [$data->cover_media_uuid], 'cover');
            }

            return $article;
        });
    }
}
