<?php

namespace Modules\Event\Features\EventModeration\Actions;

use App\Models\Province;
use App\Models\Ward;
use Modules\Event\Enums\EventLocationType;
use Modules\Event\Features\EventModeration\Data\EventData;

/**
 * Dùng chung bởi CreateEventAction/UpdateEventAction — tránh lặp lại logic dựng full_address
 * và loại bỏ field không thuộc location_type/price_type hiện tại (spec §5.5: cột không dùng
 * KHÔNG nên âm thầm giữ giá trị cũ khi người dùng đổi lựa chọn, vd đổi online→physical rồi
 * lại physical→online phải xoá sạch venue_name/venue_address cũ).
 */
class BuildEventAttributesAction
{
    /** @return array<string, mixed> */
    public function handle(EventData $data): array
    {
        $isPhysical = $data->location_type === EventLocationType::Physical->value;

        // spec §10.6 — <x-address-picker> chỉ submit province_code/ward_code (không có tên đi
        // kèm), và kể cả có gửi tên từ client cũng không nên tin (form public sau này không xác
        // thực) — LUÔN tra lại tên thật từ bảng provinces/wards ở tầng Action.
        $provinceName = $isPhysical && $data->province_code
            ? Province::where('province_code', $data->province_code)->value('name')
            : null;
        $wardName = $isPhysical && $data->ward_code
            ? Ward::where('ward_code', $data->ward_code)->value('name')
            : null;

        return [
            'category_id'       => $data->category_id,
            'title'             => $data->title,
            'short_title'       => $data->short_title,
            'description'       => $data->description,
            'start_date'        => $data->start_date,
            'end_date'          => $data->end_date,
            'start_time'        => $data->start_time,
            'end_time'          => $data->end_time,
            'location_type'     => $data->location_type,
            'venue_name'        => $isPhysical ? $data->venue_name : null,
            'venue_address'     => $isPhysical ? $data->venue_address : null,
            'province_code'     => $isPhysical ? $data->province_code : null,
            'province_name'     => $provinceName,
            'ward_code'         => $isPhysical ? $data->ward_code : null,
            'ward_name'         => $wardName,
            'full_address'      => $isPhysical ? $this->buildFullAddress($data, $provinceName, $wardName) : null,
            'online_url'        => $isPhysical ? null : $data->online_url,
            'website_url'       => $data->website_url,
            'price_type'        => $data->price_type,
            'price_amount'      => $data->price_type === 'single' ? $data->price_amount : null,
            'price_min'         => $data->price_type === 'range' ? $data->price_min : null,
            'price_max'         => $data->price_type === 'range' ? $data->price_max : null,
            'poster_alt'        => $data->poster_alt,
            'poster_width'      => $data->poster_width,
            'poster_height'     => $data->poster_height,
            'poster_size_bytes' => $data->poster_size_bytes,
            'is_featured'       => $data->is_featured,
        ];
    }

    /** "{venue_address}, {ward_name}, {province_name}" — bỏ qua phần rỗng (spec §5.5). */
    private function buildFullAddress(EventData $data, ?string $provinceName, ?string $wardName): ?string
    {
        $parts = array_filter([$data->venue_address, $wardName, $provinceName]);

        return $parts ? implode(', ', $parts) : null;
    }
}
