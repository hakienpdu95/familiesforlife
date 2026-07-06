<?php

namespace Modules\OcopRubric\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\OcopRubric\Models\OcopStarBand;

/**
 * 5 hạng sao OCOP theo Điều 3.3, QĐ 26/2026/QĐ-TTg — dùng chung cho MỌI bộ sản phẩm,
 * không lặp lại theo từng rubric_version (xem spec §18 Key Design Decisions).
 *
 * Chỉ hạng 3★ trở lên có is_certifiable=true (Điều 4.1, 4.3): 1★/2★ không có quyết định
 * công nhận, không cấp giấy chứng nhận.
 */
class OcopStarBandSeeder extends Seeder
{
    private const LEGAL_VERSION = 'QD26-2026';

    private array $bands = [
        ['star_rank' => 1, 'label' => 'Hạng 1 sao',                  'min_score' => 0,  'max_score' => 30,  'authority_level' => 'commune_screen_only', 'is_certifiable' => false],
        ['star_rank' => 2, 'label' => 'Hạng 2 sao',                  'min_score' => 30, 'max_score' => 50,  'authority_level' => 'commune_screen_only', 'is_certifiable' => false],
        ['star_rank' => 3, 'label' => 'Hạng 3 sao (cấp tỉnh)',       'min_score' => 50, 'max_score' => 70,  'authority_level' => 'province',            'is_certifiable' => true],
        ['star_rank' => 4, 'label' => 'Hạng 4 sao (cấp tỉnh)',       'min_score' => 70, 'max_score' => 90,  'authority_level' => 'province',            'is_certifiable' => true],
        ['star_rank' => 5, 'label' => 'Hạng 5 sao (cấp trung ương)', 'min_score' => 90, 'max_score' => 100, 'authority_level' => 'central',              'is_certifiable' => true],
    ];

    public function run(): void
    {
        foreach ($this->bands as $band) {
            OcopStarBand::updateOrCreate(
                ['legal_version' => self::LEGAL_VERSION, 'star_rank' => $band['star_rank']],
                $band,
            );
        }
    }
}
