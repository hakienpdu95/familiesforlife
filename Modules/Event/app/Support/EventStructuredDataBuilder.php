<?php

namespace Modules\Event\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Event\Enums\EventLocationType;
use Modules\Event\Enums\EventPriceType;
use Modules\Event\Models\Event;

/**
 * GEO/AEO (2026-08-11, nguồn tham khảo spec/giadinh.md — "Schema Markup for AI: Types,
 * Benefits, & How to Set It Up") — trang chi tiết sự kiện public trước đây không phát sinh
 * JSON-LD nào, dù đã hiển thị đủ thời gian/địa điểm/giá vé cho người đọc (xem public/show.
 * blade.php). Mirror đúng cấu trúc `Modules\Post\Support\ArticleStructuredDataBuilder` — 1
 * node Event duy nhất, chỉ điền field có dữ liệu THẬT, không suy diễn (VD không có field
 * `organizer` trong bảng events nên KHÔNG khai — xem lý do tương tự ArticleStructuredDataBuilder
 * ::buildOffer() thà thiếu còn hơn khai sai).
 *
 * `eventStatus` hardcode EventScheduled vì builder này chỉ bao giờ chạy trên sự kiện
 * PublicEventController::show() đã lọc status=Published (ExpirePastEventsJob chuyển sự kiện
 * quá hạn sang Expired, không còn khớp query đó nữa) — không cần map 6 case EventStatus.
 */
class EventStructuredDataBuilder
{
    public function build(Event $event, string $canonicalUrl): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'url' => $canonicalUrl,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $this->buildAttendanceMode($event),
            'location' => $this->buildLocation($event),
        ];

        $startDate = $this->combineDateTime($event->start_date, $event->start_time);
        if ($startDate) {
            $schema['startDate'] = $startDate;
        }

        $endDate = $this->combineDateTime($event->end_date, $event->end_time);
        if ($endDate) {
            $schema['endDate'] = $endDate;
        }

        if ($event->description) {
            $schema['description'] = trim(strip_tags($event->description));
        }

        if ($event->poster_path) {
            $schema['image'] = [url(Storage::url($event->poster_path))];
        }

        $offers = $this->buildOffers($event);
        if ($offers) {
            $schema['offers'] = $offers;
        }

        return $schema;
    }

    private function buildAttendanceMode(Event $event): string
    {
        return $event->location_type === EventLocationType::Online
            ? 'https://schema.org/OnlineEventAttendanceMode'
            : 'https://schema.org/OfflineEventAttendanceMode';
    }

    /** VirtualLocation cho sự kiện online, Place cho sự kiện trực tiếp — cùng phân nhánh $event->locationLabel(). */
    private function buildLocation(Event $event): array
    {
        if ($event->location_type === EventLocationType::Online) {
            $location = ['@type' => 'VirtualLocation'];

            if ($event->online_url) {
                $location['url'] = $event->online_url;
            }

            return $location;
        }

        $address = ['@type' => 'PostalAddress', 'addressCountry' => 'VN'];

        if ($event->venue_address) {
            $address['streetAddress'] = $event->venue_address;
        }

        if ($event->ward?->name) {
            $address['addressLocality'] = $event->ward->name;
        }

        if ($event->province?->name) {
            $address['addressRegion'] = $event->province->name;
        }

        $place = [
            '@type' => 'Place',
            'name' => $event->venue_name ?: $event->title,
            'address' => $address,
        ];

        if ($event->latitude !== null && $event->longitude !== null) {
            $place['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $event->latitude,
                'longitude' => (float) $event->longitude,
            ];
        }

        return $place;
    }

    /**
     * `start_time`/`end_time` nullable = "cả ngày" (xem comment migration events) — chỉ ghép
     * giờ vào khi có, không suy diễn 00:00.
     */
    private function combineDateTime(?Carbon $date, mixed $time): ?string
    {
        if (! $date) {
            return null;
        }

        if (! $time) {
            return $date->toDateString();
        }

        return Carbon::parse($date->toDateString().' '.$time)->toIso8601String();
    }

    /**
     * VND-only (cùng quyết định `Event::priceLabel()`). `Range` dùng AggregateOffer
     * (lowPrice/highPrice) — schema.org không cho 1 `Offer` có 2 giá. KHÔNG khai `availability`
     * vì bảng events không lưu trạng thái còn/hết vé thật (thà thiếu còn hơn khai sai).
     */
    private function buildOffers(Event $event): ?array
    {
        $offer = match ($event->price_type) {
            EventPriceType::Free => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'VND',
            ],
            EventPriceType::Single => $event->price_amount === null ? null : [
                '@type' => 'Offer',
                'price' => (string) $event->price_amount,
                'priceCurrency' => 'VND',
            ],
            EventPriceType::Range => ($event->price_min === null || $event->price_max === null) ? null : [
                '@type' => 'AggregateOffer',
                'lowPrice' => (string) $event->price_min,
                'highPrice' => (string) $event->price_max,
                'priceCurrency' => 'VND',
            ],
        };

        if ($offer && $event->website_url) {
            $offer['url'] = $event->website_url;
        }

        return $offer;
    }
}
