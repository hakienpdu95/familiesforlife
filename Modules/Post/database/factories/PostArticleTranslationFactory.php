<?php

namespace Modules\Post\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticleTranslation;

/**
 * @extends Factory<PostArticleTranslation>
 */
class PostArticleTranslationFactory extends Factory
{
    protected $model = PostArticleTranslation::class;

    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'uuid'    => (string) Str::uuid(),
            'locale'  => 'vi',
            'title'   => $title,
            'slug'    => Str::slug($title) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'excerpt' => fake()->paragraph(),
            'status'  => TranslationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status'       => TranslationStatus::Published,
            'published_at' => now(),
        ]);
    }

    /** Chỉ hợp lệ khi article cha có is_sponsored=true (đúng validation §6.2) — factory không tự bật is_sponsored hộ. */
    public function withDisclosure(string $sponsorName = 'Nhãn hàng ABC'): static
    {
        return $this->state(fn () => [
            'disclosure_text' => "Nội dung tài trợ bởi {$sponsorName}",
            'cta_text'        => 'Tìm hiểu thêm',
            'cta_url'         => 'https://example.com',
        ]);
    }
}
