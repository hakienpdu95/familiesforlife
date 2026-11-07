<?php

namespace Modules\Heritage\Features\Traceability;

use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Heritage\Enums\HeritageSiteStatus;
use Modules\Heritage\Enums\HeritageVisitingStatus;
use Modules\Heritage\Models\HeritageSite;

class HeritageSiteTracePresenter implements TracePresenter
{
    private const INTRO_CHARS = 600;

    public function view(): string
    {
        return 'heritage::traceability.site';
    }

    public function constrain(Builder $query): void
    {
        $query->withTrashed()->with('media');
    }

    /** @param  HeritageSite  $target */
    public function isPublic(Model $target): bool
    {
        return $target->status === HeritageSiteStatus::Published;
    }

    /** @param  HeritageSite  $target */
    public function snapshot(Model $target): array
    {
        $intro = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $target->description)));

        return [
            'name' => $target->name,
            'type' => $target->heritage_type?->label(),
            'rank' => $target->rank?->label(),
            'era' => $target->era,
            'address' => implode(', ', array_filter([$target->address, $target->ward_name, $target->province_name])) ?: null,
            'latitude' => $target->latitude !== null ? (float) $target->latitude : null,
            'longitude' => $target->longitude !== null ? (float) $target->longitude : null,
            'intro' => $intro !== '' ? Str::limit($intro, self::INTRO_CHARS) : null,
            'image' => TraceabilityService::imageRef($target, 'cover'),
        ];
    }

    /** @param  HeritageSite|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $lat = $payload['latitude'] ?? null;
        $lng = $payload['longitude'] ?? null;

        return $this->base([
            'title' => $payload['name'] ?? 'Di tích',
            'image' => $payload['image']['url'] ?? null,
            'type' => $payload['type'] ?? null,
            'rank' => $payload['rank'] ?? null,
            'era' => $payload['era'] ?? null,
            'address' => $payload['address'] ?? null,
            'map_url' => $lat && $lng ? 'https://www.google.com/maps/search/?api=1&query='.$lat.','.$lng : null,
            'intro' => $live ? (filled($live->description) ? trim(strip_tags($live->description)) : null) : ($payload['intro'] ?? null),
            'content_html' => $live && filled($live->content) ? $live->content : null,
            'visiting' => $live ? $this->visiting($live) : null,
            'cta' => $live
                ? ['url' => route('heritage.public.show', ['slug' => $live->slug, 'id' => $live->id]), 'label' => 'Xem trang giới thiệu di tích']
                : ['url' => route('heritage.public.index'), 'label' => 'Khám phá di sản khác'],
        ]);
    }

    /** @param  HeritageSite  $target */
    public function pending(Model $target): array
    {
        return $this->base([
            'title' => $target->name,
            'notice' => 'Thông tin chi tiết đang được cập nhật.',
        ]);
    }

    private function visiting(HeritageSite $site): ?array
    {
        if (! $site->visiting_status || $site->visiting_status === HeritageVisitingStatus::Unknown) {
            return null;
        }

        return [
            'label' => $site->visiting_status->label(),
            'tone' => match ($site->visiting_status) {
                HeritageVisitingStatus::Open => 'success',
                HeritageVisitingStatus::Restoring => 'warning',
                default => 'error',
            },
        ];
    }

    private function base(array $page): array
    {
        return TraceabilityService::page([
            'heading' => 'Thông tin di tích',
            'icon' => 'fa-landmark',
            'type' => null,
            'rank' => null,
            'era' => null,
            'address' => null,
            'visiting' => null,
            'map_url' => null,
            'intro' => null,
            'content_html' => null,
            'archive_text' => 'Cảnh báo: Thông tin dưới đây là bản lưu trữ. Di tích này hiện đã bị gỡ khỏi hệ thống.',
            'cta' => ['url' => route('heritage.public.index'), 'label' => 'Khám phá di sản khác'],
            ...$page,
        ]);
    }
}
