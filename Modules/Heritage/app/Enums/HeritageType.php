<?php

namespace Modules\Heritage\Enums;

/**
 * spec/Heritage_Technical_Specification.md §3.1 — theo phân loại Luật Di sản văn hóa Việt Nam
 * (nhóm "di sản vật thể") + di sản văn hóa phi vật thể theo Công ước UNESCO 2003.
 */
enum HeritageType: string
{
    case HistoricalMonument = 'historical_monument';   // Di tích lịch sử - văn hóa
    case ArchitecturalArt = 'architectural_art';      // Di tích kiến trúc nghệ thuật
    case Archaeological = 'archaeological';         // Di tích khảo cổ
    case ScenicLandscape = 'scenic_landscape';       // Danh lam thắng cảnh
    case Intangible = 'intangible';             // Di sản văn hóa phi vật thể (làng nghề, lễ hội, nghệ thuật trình diễn...)

    public function label(): string
    {
        return match ($this) {
            self::HistoricalMonument => 'Di tích lịch sử - văn hóa',
            self::ArchitecturalArt => 'Di tích kiến trúc nghệ thuật',
            self::Archaeological => 'Di tích khảo cổ',
            self::ScenicLandscape => 'Danh lam thắng cảnh',
            self::Intangible => 'Di sản văn hóa phi vật thể',
        };
    }
}
