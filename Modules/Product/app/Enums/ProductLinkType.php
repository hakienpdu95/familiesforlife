<?php

namespace Modules\Product\Enums;

/**
 * 4 kênh link affiliate cố định cấu hình sẵn ở tầng sản phẩm (docs/product-catalog-spec.md §6.2/§7).
 * Module Post chỉ chọn "dùng link nào" cho từng vị trí chèn, không nhập tay URL —
 * xem docs/post-module-spec.md §9.1 bước 5 / §9.5 / §9.6.
 */
enum ProductLinkType: string
{
    case Shopee            = 'shopee';
    case TikTok             = 'tiktok';
    case SupplierProduct    = 'supplier_product';    // link sản phẩm tại NCC
    case SupplierHomepage   = 'supplier_homepage';   // trang chủ/fanpage NCC (fallback)

    public function label(): string
    {
        return match ($this) {
            self::Shopee           => 'Mua trên Shopee',
            self::TikTok           => 'Mua trên TikTok',
            self::SupplierProduct  => 'Xem tại nhà cung cấp',
            self::SupplierHomepage => 'Website nhà cung cấp',
        };
    }

    public function logo(): ?string
    {
        return match ($this) {
            self::Shopee => 'images/shopee.png',
            self::TikTok => 'images/tiktok.png',
            default      => null,
        };
    }

    public function brandClass(): ?string
    {
        return match ($this) {
            self::Shopee => 'border-0 bg-[#ee4d2d] text-white hover:bg-[#d73211]',
            self::TikTok => 'border-0 bg-[#161823] text-white hover:bg-black',
            default      => null,
        };
    }

    public static function fromUrl(?string $url): ?self
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        return match (true) {
            $host === '' => null,
            (bool) preg_match('/(^|\.)(shopee\.vn|shp\.ee)$/', $host) => self::Shopee,
            (bool) preg_match('/(^|\.)tiktok\.com$/', $host) => self::TikTok,
            default => null,
        };
    }

    /** Cột tương ứng trên bảng `products`. */
    public function urlColumn(): string
    {
        return match ($this) {
            self::Shopee           => 'shopee_url',
            self::TikTok           => 'tiktok_url',
            self::SupplierProduct  => 'supplier_url',
            self::SupplierHomepage => 'supplier_homepage_url',
        };
    }
}
