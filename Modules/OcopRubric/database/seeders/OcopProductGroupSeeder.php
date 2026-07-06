<?php

namespace Modules\OcopRubric\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\OcopRubric\Models\OcopProductGroup;

/**
 * 26 "Bộ sản phẩm" theo Phụ lục I, QĐ 26/2026/QĐ-TTg — đối chiếu trực tiếp
 * docs/bnn_txng.html (Phụ lục I, dòng 723–1389). 6 Ngành (I–VI) → 17 Nhóm →
 * 26 Bộ sản phẩm (lá của Phụ lục I).
 *
 * requires_sample_product = false CHỈ cho bộ #26 "Dịch vụ du lịch cộng đồng..."
 * (Điều 6.2.d — không yêu cầu 05 sản phẩm mẫu kèm hồ sơ cho nhóm dịch vụ).
 */
class OcopProductGroupSeeder extends Seeder
{
    private array $groups = [
        // ── I. SẢN PHẨM THỰC PHẨM (6 nhóm → 13 bộ sản phẩm) ────────────────
        ['code' => 'rau-cu-qua-hat-tuoi',        'name' => 'Rau, củ, quả, hạt tươi',                       'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Thực phẩm tươi sống', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'thit-thuy-san-trung-sua-tuoi', 'name' => 'Thịt, thủy sản, trứng, sữa tươi',              'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Thực phẩm tươi sống', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'gao-ngu-coc-hat-so-che',      'name' => 'Gạo, ngũ cốc, hạt sơ chế khác',                'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Thực phẩm thô, sơ chế', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'mat-ong-mat-khac-tpthuc-khac', 'name' => 'Mật ong, mật khác và nông sản thực phẩm khác', 'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Thực phẩm thô, sơ chế', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'do-an-nhanh',                  'name' => 'Đồ ăn nhanh',                                  'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Thực phẩm chế biến', 'managing_agency' => 'Bộ Công Thương'],
        ['code' => 'che-bien-tu-gao-ngu-coc',      'name' => 'Chế biến từ gạo, ngũ cốc',                     'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Thực phẩm chế biến', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường; Bộ Công Thương'],
        ['code' => 'che-bien-tu-rau-cu-qua-hat',   'name' => 'Chế biến từ rau, củ, quả, hạt',                'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Thực phẩm chế biến', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'che-bien-tu-thit-trung-sua-ts', 'name' => 'Chế biến từ thịt, trứng, sữa, thủy sản, các sản phẩm từ mật ong, mật khác và nông sản thực phẩm khác', 'industry_code' => 'I', 'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Thực phẩm chế biến', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường; Bộ Công Thương'],
        ['code' => 'tuong-nuoc-mam-gia-vi-dang-long', 'name' => 'Tương, nước mắm, gia vị dạng lỏng khác',    'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Gia vị', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'gia-vi-khac',                  'name' => 'Gia vị khác',                                  'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Gia vị', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'che-tuoi-che-bien',            'name' => 'Chè tươi, chế biến',                           'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Chè', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'san-pham-che-tu-thuc-vat-khac', 'name' => 'Sản phẩm chè từ thực vật khác',               'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Chè', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'ca-phe-ca-cao',                'name' => 'Cà phê, ca cao',                               'industry_code' => 'I',   'industry_name' => 'SẢN PHẨM THỰC PHẨM', 'group_label' => 'Nhóm: Cà phê, ca cao', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],

        // ── II. SẢN PHẨM ĐỒ UỐNG (2 nhóm → 4 bộ sản phẩm) ──────────────────
        ['code' => 'ruou-trang',                  'name' => 'Rượu trắng',                                   'industry_code' => 'II',  'industry_name' => 'SẢN PHẨM ĐỒ UỐNG', 'group_label' => 'Nhóm: Đồ uống có cồn', 'managing_agency' => 'Bộ Công Thương'],
        ['code' => 'do-uong-co-con-khac',          'name' => 'Đồ uống có cồn khác',                          'industry_code' => 'II',  'industry_name' => 'SẢN PHẨM ĐỒ UỐNG', 'group_label' => 'Nhóm: Đồ uống có cồn', 'managing_agency' => 'Bộ Công Thương'],
        ['code' => 'nuoc-khoang-nuoc-uong-dong-chai', 'name' => 'Nước khoáng thiên nhiên, nước uống đóng chai', 'industry_code' => 'II', 'industry_name' => 'SẢN PHẨM ĐỒ UỐNG', 'group_label' => 'Nhóm: Đồ uống không cồn', 'managing_agency' => 'Bộ Y tế'],
        ['code' => 'do-uong-khong-con',            'name' => 'Đồ uống không cồn',                            'industry_code' => 'II',  'industry_name' => 'SẢN PHẨM ĐỒ UỐNG', 'group_label' => 'Nhóm: Đồ uống không cồn', 'managing_agency' => 'Bộ Công Thương'],

        // ── III. DƯỢC LIỆU & SẢN PHẨM TỪ DƯỢC LIỆU (3 nhóm, không phân nhóm) ─
        ['code' => 'tpcn-thuoc-duoc-lieu-co-truyen', 'name' => 'Thực phẩm chức năng, thuốc dược liệu, thuốc cổ truyền', 'industry_code' => 'III', 'industry_name' => 'SẢN PHẨM DƯỢC LIỆU VÀ SẢN PHẨM TỪ DƯỢC LIỆU', 'group_label' => 'Nhóm: Thực phẩm chức năng, thuốc dược liệu, thuốc cổ truyền', 'managing_agency' => 'Bộ Y tế'],
        ['code' => 'my-pham-tu-duoc-lieu',         'name' => 'Mỹ phẩm có thành phần từ dược liệu',            'industry_code' => 'III', 'industry_name' => 'SẢN PHẨM DƯỢC LIỆU VÀ SẢN PHẨM TỪ DƯỢC LIỆU', 'group_label' => 'Nhóm: Mỹ phẩm có thành phần từ dược liệu', 'managing_agency' => 'Bộ Y tế'],
        ['code' => 'tinh-dau-duoc-lieu-khac',      'name' => 'Tinh dầu và dược liệu khác',                    'industry_code' => 'III', 'industry_name' => 'SẢN PHẨM DƯỢC LIỆU VÀ SẢN PHẨM TỪ DƯỢC LIỆU', 'group_label' => 'Nhóm: Tinh dầu và dược liệu khác', 'managing_agency' => 'Bộ Y tế; Bộ Công Thương'],

        // ── IV. HÀNG THỦ CÔNG MỸ NGHỆ (2 nhóm, không phân nhóm) ────────────
        ['code' => 'tcmn-gia-dung-trang-tri',      'name' => 'Thủ công mỹ nghệ gia dụng, trang trí',         'industry_code' => 'IV',  'industry_name' => 'SẢN PHẨM HÀNG THỦ CÔNG MỸ NGHỆ', 'group_label' => 'Nhóm: Thủ công mỹ nghệ gia dụng, trang trí', 'managing_agency' => 'Bộ Công Thương; Bộ Nông nghiệp và Môi trường'],
        ['code' => 'vai-may-mac',                  'name' => 'Vải, may mặc',                                  'industry_code' => 'IV',  'industry_name' => 'SẢN PHẨM HÀNG THỦ CÔNG MỸ NGHỆ', 'group_label' => 'Nhóm: Vải, may mặc', 'managing_agency' => 'Bộ Công Thương'],

        // ── V. SINH VẬT CẢNH (3 nhóm, không phân nhóm) ─────────────────────
        ['code' => 'hoa',                          'name' => 'Hoa',                                          'industry_code' => 'V',   'industry_name' => 'SẢN PHẨM SINH VẬT CẢNH', 'group_label' => 'Nhóm: Hoa', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'cay-canh',                     'name' => 'Cây cảnh',                                     'industry_code' => 'V',   'industry_name' => 'SẢN PHẨM SINH VẬT CẢNH', 'group_label' => 'Nhóm: Cây cảnh', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],
        ['code' => 'dong-vat-canh',                 'name' => 'Động vật cảnh',                                'industry_code' => 'V',   'industry_name' => 'SẢN PHẨM SINH VẬT CẢNH', 'group_label' => 'Nhóm: Động vật cảnh', 'managing_agency' => 'Bộ Nông nghiệp và Môi trường'],

        // ── VI. DỊCH VỤ DU LỊCH CỘNG ĐỒNG (1 nhóm, không phân nhóm) ────────
        ['code' => 'dv-du-lich-cong-dong-sinh-thai', 'name' => 'Dịch vụ du lịch cộng đồng, du lịch sinh thái và điểm du lịch', 'industry_code' => 'VI', 'industry_name' => 'SẢN PHẨM DỊCH VỤ DU LỊCH CỘNG ĐỒNG, DU LỊCH SINH THÁI VÀ ĐIỂM DU LỊCH', 'group_label' => 'Nhóm: Dịch vụ du lịch cộng đồng, du lịch sinh thái và điểm du lịch', 'managing_agency' => 'Bộ Văn hóa, Thể thao và Du lịch; Bộ Nông nghiệp và Môi trường', 'requires_sample_product' => false],
    ];

    public function run(): void
    {
        foreach ($this->groups as $index => $group) {
            OcopProductGroup::updateOrCreate(
                ['code' => $group['code']],
                array_merge([
                    'requires_sample_product' => true,
                    'is_active'               => true,
                    'sort_order'              => $index + 1,
                ], $group),
            );
        }
    }
}
