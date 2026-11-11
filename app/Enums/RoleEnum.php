<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'super-admin';
    case CONTENT_HEAD = 'platform_content_head';
    case CONTENT_EDITOR = 'platform_content_editor';
    case CONTENT_CREATOR = 'platform_content_creator';
    case SECTION_EDITOR = 'platform_section_editor';
    case CONTENT_MODERATOR = 'platform_content_moderator';
    case OPS = 'platform_ops';
    case VIEWER = 'platform_viewer';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::CONTENT_HEAD => __('Tổng biên tập'),
            self::CONTENT_EDITOR => __('Biên tập viên'),
            self::CONTENT_CREATOR => __('Phóng viên / Biên tập viết bài'),
            self::SECTION_EDITOR => __('Biên tập viên trưởng chuyên mục'),
            self::CONTENT_MODERATOR => __('Kiểm duyệt viên (Legal)'),
            self::OPS => __('Vận hành Platform'),
            self::VIEWER => __('Giám sát / Viewer'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'badge-error',
            self::CONTENT_HEAD => 'badge-primary',
            self::CONTENT_EDITOR => 'badge-info',
            self::CONTENT_CREATOR => 'badge-accent',
            self::SECTION_EDITOR => 'badge-secondary',
            self::CONTENT_MODERATOR => 'badge-warning',
            self::OPS => 'badge-success',
            self::VIEWER => 'badge-neutral',
        };
    }

    /** @return array<int, self> */
    public static function assignable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $r) => $r !== self::SUPER_ADMIN));
    }

    /** @return array<int, string> */
    public static function assignableValues(): array
    {
        return array_map(fn (self $r) => $r->value, self::assignable());
    }

    /** @return array<string, string> */
    public static function labels(bool $assignableOnly = false): array
    {
        $cases = $assignableOnly ? self::assignable() : self::cases();

        return array_combine(
            array_map(fn (self $r) => $r->value, $cases),
            array_map(fn (self $r) => $r->label(), $cases),
        );
    }
}
