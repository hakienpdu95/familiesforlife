<?php

namespace App\Shared\Traceability\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Mỗi phân hệ có template riêng (view()) kế thừa layouts.traceability.
 *
 * snapshot() trả payload JSON lưu vào qr_identities.snapshot — đủ để page() dựng lại trang khi
 * đối tượng gốc không còn (archive view). Ảnh lưu dạng ['url', 'media_uuid', 'conversion'] để
 * khi xoá cứng có thể chuyển file sang thư mục archived_snapshots.
 */
interface TracePresenter
{
    public function view(): string;

    public function constrain(Builder $query): void;

    public function isPublic(Model $target): bool;

    public function snapshot(Model $target): array;

    /** $live = null ⇒ đang render bản lưu trữ từ snapshot. */
    public function page(array $payload, ?Model $live): array;

    /** Đối tượng còn tồn tại nhưng chưa từng công khai. */
    public function pending(Model $target): array;
}
