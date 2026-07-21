<?php

namespace Modules\Post\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Enums\SponsorLabel;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;

/**
 * @extends Factory<PostArticle>
 */
class PostArticleFactory extends Factory
{
    protected $model = PostArticle::class;

    public function definition(): array
    {
        return [
            'uuid'            => (string) Str::uuid(),
            'main_locale'     => 'vi',
            'format'          => ArticleFormat::Article,
            'is_featured'     => false,
            'sort_order'      => 0,
            'is_sponsored'    => false, // mặc định KHÔNG sponsored — đúng dữ liệu thật sau migrate
            'created_by'      => User::factory(),
        ];
    }

    public function sponsored(): static
    {
        return $this->state(fn () => [
            'is_sponsored'         => true,
            'sponsor_name'         => fake()->company(),
            'sponsor_logo_url'     => fake()->imageUrl(),
            'sponsor_label'        => SponsorLabel::Sponsored,
            'campaign_code'        => strtoupper(fake()->bothify('CAMP-####')),
            'sponsored_start_date' => now()->subDay()->toDateString(),
            'sponsored_end_date'   => now()->addDays(30)->toDateString(),
        ]);
    }

    /** Tạo kèm 1 PostArticleTranslation mặc định — dùng afterCreating, không phải quan hệ has-one giả. */
    public function withTranslation(array $translationAttributes = []): static
    {
        return $this->afterCreating(function (PostArticle $article) use ($translationAttributes) {
            PostArticleTranslation::factory()
                ->for($article, 'article')
                ->create(array_merge([
                    'locale' => $article->main_locale,
                ], $translationAttributes));
        });
    }

    /**
     * Gộp sponsored() + 1 translation đã published + disclosure_text hợp lệ + sponsored_published_at
     * đã set — dựng thẳng đúng trạng thái test case §14 mục 6 (job hết hạn) và mục 10
     * (isCurrentlySponsored() trước khi job chạy) cần.
     */
    public function sponsoredAndPublished(): static
    {
        return $this->sponsored()
            ->withTranslation([
                'status'       => TranslationStatus::Published,
                'published_at' => now()->subDay(),
            ])
            ->afterCreating(function (PostArticle $article) {
                $article->mainTranslation()?->update([
                    'disclosure_text' => "Nội dung tài trợ bởi {$article->sponsor_name}",
                ]);
                $article->update(['sponsored_published_at' => now()->subDay()]);
            });
    }

    /** sponsoredAndPublished() + sponsored_end_date đã qua hôm qua — đúng thẳng setup test case §14 mục 6/10. */
    public function sponsoredAndExpired(): static
    {
        return $this->sponsoredAndPublished()->state(fn () => [
            'sponsored_end_date' => now()->subDay()->toDateString(),
        ]);
    }
}
