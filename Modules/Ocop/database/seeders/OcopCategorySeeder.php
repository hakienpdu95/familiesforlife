<?php

namespace Modules\Ocop\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Ocop\Models\OcopCategory;

/**
 * spec/danhmuc.html — bảng phân loại sản phẩm OCOP chính thức (nhà nước quy định, thống nhất
 * toàn quốc, không tùy biến theo module/dev). 3 cấp: Nhóm lớn (I–VI) → Nhóm → Phân nhóm,
 * "authority" (Cơ quan chủ trì quản lý) chỉ gắn ở cấp sâu nhất của mỗi nhánh — đúng bảng gốc.
 *
 * Idempotent — match theo (parent_id, code), bỏ qua nếu đã tồn tại; an toàn chạy lại nhiều lần.
 * Chạy TRƯỚC ProvinceShowcaseDemoSeeder (seedOcopProducts() gán category_id vào các phân nhóm
 * chính thức ở đây, không còn tạo danh mục "food"/"craft" tùy tiện như trước).
 */
class OcopCategorySeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, authority?: string, children?: array}>
     */
    private const TAXONOMY = [
        ['code' => 'I', 'name' => 'SẢN PHẨM THỰC PHẨM', 'children' => [
            ['code' => '1', 'name' => 'Nhóm: Thực phẩm tươi sống', 'children' => [
                ['code' => 'a', 'name' => 'Phân nhóm: Rau, củ, quả, hạt tươi', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
                ['code' => 'b', 'name' => 'Phân nhóm: Thịt, thủy sản, trứng, sữa tươi', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
            ]],
            ['code' => '2', 'name' => 'Nhóm: Thực phẩm thô, sơ chế', 'children' => [
                ['code' => 'a', 'name' => 'Phân nhóm: Gạo, ngũ cốc, hạt sơ chế khác', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
                ['code' => 'b', 'name' => 'Phân nhóm: Mật ong, mật khác và nông sản thực phẩm khác', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
            ]],
            ['code' => '3', 'name' => 'Nhóm: Thực phẩm chế biến', 'children' => [
                ['code' => 'a', 'name' => 'Phân nhóm: Đồ ăn nhanh', 'authority' => 'Bộ Công Thương'],
                ['code' => 'b', 'name' => 'Phân nhóm: Chế biến từ gạo, ngũ cốc', 'authority' => 'Bộ Nông nghiệp và Môi trường; Bộ Công Thương'],
                ['code' => 'c', 'name' => 'Phân nhóm: Chế biến từ rau, củ, quả, hạt', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
                ['code' => 'd', 'name' => 'Phân nhóm: Chế biến từ thịt, trứng, sữa, thủy sản, các sản phẩm từ mật ong, mật khác và nông sản thực phẩm khác', 'authority' => 'Bộ Nông nghiệp và Môi trường; Bộ Công Thương'],
            ]],
            ['code' => '4', 'name' => 'Nhóm: Gia vị', 'children' => [
                ['code' => 'a', 'name' => 'Phân nhóm: Tương, nước mắm, gia vị dạng lỏng khác', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
                ['code' => 'b', 'name' => 'Phân nhóm: Gia vị khác', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
            ]],
            ['code' => '5', 'name' => 'Nhóm: Chè', 'children' => [
                ['code' => 'a', 'name' => 'Phân nhóm: Chè tươi, chế biến', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
                ['code' => 'b', 'name' => 'Phân nhóm: Sản phẩm chè từ thực vật khác', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
            ]],
            ['code' => '6', 'name' => 'Nhóm: Cà phê, ca cao', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
        ]],
        ['code' => 'II', 'name' => 'SẢN PHẨM ĐỒ UỐNG', 'children' => [
            ['code' => '1', 'name' => 'Nhóm: Đồ uống có cồn', 'children' => [
                ['code' => 'a', 'name' => 'Phân nhóm: Rượu trắng', 'authority' => 'Bộ Công Thương'],
                ['code' => 'b', 'name' => 'Phân nhóm: Đồ uống có cồn khác', 'authority' => 'Bộ Công Thương'],
            ]],
            ['code' => '2', 'name' => 'Nhóm: Đồ uống không cồn', 'children' => [
                ['code' => 'a', 'name' => 'Phân nhóm: Nước khoáng thiên nhiên, nước uống đóng chai', 'authority' => 'Bộ Y tế'],
                ['code' => 'b', 'name' => 'Phân nhóm: Đồ uống không cồn', 'authority' => 'Bộ Công Thương'],
            ]],
        ]],
        ['code' => 'III', 'name' => 'SẢN PHẨM DƯỢC LIỆU VÀ SẢN PHẨM TỪ DƯỢC LIỆU', 'children' => [
            ['code' => '1', 'name' => 'Nhóm: Thực phẩm chức năng, thuốc dược liệu, thuốc cổ truyền', 'authority' => 'Bộ Y tế'],
            ['code' => '2', 'name' => 'Nhóm: Mỹ phẩm có thành phần từ dược liệu', 'authority' => 'Bộ Y tế'],
            ['code' => '3', 'name' => 'Nhóm: Tinh dầu và dược liệu khác', 'authority' => 'Bộ Y tế; Bộ Công Thương'],
        ]],
        ['code' => 'IV', 'name' => 'SẢN PHẨM HÀNG THỦ CÔNG MỸ NGHỆ', 'children' => [
            ['code' => '1', 'name' => 'Nhóm: Thủ công mỹ nghệ gia dụng, trang trí', 'authority' => 'Bộ Công Thương; Bộ Nông nghiệp và Môi trường'],
            ['code' => '2', 'name' => 'Nhóm: Vải, may mặc', 'authority' => 'Bộ Công Thương'],
        ]],
        ['code' => 'V', 'name' => 'SẢN PHẨM SINH VẬT CẢNH', 'children' => [
            ['code' => '1', 'name' => 'Nhóm: Hoa', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
            ['code' => '2', 'name' => 'Nhóm: Cây cảnh', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
            ['code' => '3', 'name' => 'Nhóm: Động vật cảnh', 'authority' => 'Bộ Nông nghiệp và Môi trường'],
        ]],
        ['code' => 'VI', 'name' => 'SẢN PHẨM DỊCH VỤ DU LỊCH CỘNG ĐỒNG, DU LỊCH SINH THÁI VÀ ĐIỂM DU LỊCH', 'children' => [
            ['code' => '1', 'name' => 'Nhóm: Dịch vụ du lịch cộng đồng, du lịch sinh thái và điểm du lịch', 'authority' => 'Bộ Văn hóa, Thể thao và Du lịch; Bộ Nông nghiệp và Môi trường'],
        ]],
    ];

    /** spec/I18n_Specification.md §5.4 — tên tiếng Anh của bảng phân loại, ghi vào ocop_category_translations mỗi lần chạy. */
    private const EN_NAMES = [
        'SẢN PHẨM THỰC PHẨM' => 'FOOD PRODUCTS',
        'Nhóm: Thực phẩm tươi sống' => 'Group: Fresh food',
        'Phân nhóm: Rau, củ, quả, hạt tươi' => 'Subgroup: Fresh vegetables, tubers, fruits and nuts',
        'Phân nhóm: Thịt, thủy sản, trứng, sữa tươi' => 'Subgroup: Meat, seafood, eggs and fresh milk',
        'Nhóm: Thực phẩm thô, sơ chế' => 'Group: Raw and minimally processed food',
        'Phân nhóm: Gạo, ngũ cốc, hạt sơ chế khác' => 'Subgroup: Rice, cereals and other minimally processed grains',
        'Phân nhóm: Mật ong, mật khác và nông sản thực phẩm khác' => 'Subgroup: Honey, other syrups and other agricultural food products',
        'Nhóm: Thực phẩm chế biến' => 'Group: Processed food',
        'Phân nhóm: Đồ ăn nhanh' => 'Subgroup: Fast food',
        'Phân nhóm: Chế biến từ gạo, ngũ cốc' => 'Subgroup: Processed rice and cereal products',
        'Phân nhóm: Chế biến từ rau, củ, quả, hạt' => 'Subgroup: Processed vegetable, tuber, fruit and nut products',
        'Phân nhóm: Chế biến từ thịt, trứng, sữa, thủy sản, các sản phẩm từ mật ong, mật khác và nông sản thực phẩm khác' => 'Subgroup: Processed meat, egg, dairy and seafood products, honey and syrup products and other agricultural food products',
        'Nhóm: Gia vị' => 'Group: Spices and condiments',
        'Phân nhóm: Tương, nước mắm, gia vị dạng lỏng khác' => 'Subgroup: Sauces, fish sauce and other liquid condiments',
        'Phân nhóm: Gia vị khác' => 'Subgroup: Other spices and condiments',
        'Nhóm: Chè' => 'Group: Tea',
        'Phân nhóm: Chè tươi, chế biến' => 'Subgroup: Fresh and processed tea',
        'Phân nhóm: Sản phẩm chè từ thực vật khác' => 'Subgroup: Herbal teas from other plants',
        'Nhóm: Cà phê, ca cao' => 'Group: Coffee and cocoa',
        'SẢN PHẨM ĐỒ UỐNG' => 'BEVERAGE PRODUCTS',
        'Nhóm: Đồ uống có cồn' => 'Group: Alcoholic beverages',
        'Phân nhóm: Rượu trắng' => 'Subgroup: White liquor',
        'Phân nhóm: Đồ uống có cồn khác' => 'Subgroup: Other alcoholic beverages',
        'Nhóm: Đồ uống không cồn' => 'Group: Non-alcoholic beverages',
        'Phân nhóm: Nước khoáng thiên nhiên, nước uống đóng chai' => 'Subgroup: Natural mineral water and bottled drinking water',
        'Phân nhóm: Đồ uống không cồn' => 'Subgroup: Non-alcoholic beverages',
        'SẢN PHẨM DƯỢC LIỆU VÀ SẢN PHẨM TỪ DƯỢC LIỆU' => 'MEDICINAL HERBS AND HERBAL PRODUCTS',
        'Nhóm: Thực phẩm chức năng, thuốc dược liệu, thuốc cổ truyền' => 'Group: Dietary supplements, herbal medicines and traditional medicines',
        'Nhóm: Mỹ phẩm có thành phần từ dược liệu' => 'Group: Cosmetics with herbal ingredients',
        'Nhóm: Tinh dầu và dược liệu khác' => 'Group: Essential oils and other medicinal herbs',
        'SẢN PHẨM HÀNG THỦ CÔNG MỸ NGHỆ' => 'HANDICRAFT PRODUCTS',
        'Nhóm: Thủ công mỹ nghệ gia dụng, trang trí' => 'Group: Household and decorative handicrafts',
        'Nhóm: Vải, may mặc' => 'Group: Textiles and garments',
        'SẢN PHẨM SINH VẬT CẢNH' => 'ORNAMENTAL PLANTS AND ANIMALS',
        'Nhóm: Hoa' => 'Group: Flowers',
        'Nhóm: Cây cảnh' => 'Group: Ornamental plants',
        'Nhóm: Động vật cảnh' => 'Group: Ornamental animals',
        'SẢN PHẨM DỊCH VỤ DU LỊCH CỘNG ĐỒNG, DU LỊCH SINH THÁI VÀ ĐIỂM DU LỊCH' => 'COMMUNITY TOURISM, ECO-TOURISM SERVICES AND TOURIST DESTINATIONS',
        'Nhóm: Dịch vụ du lịch cộng đồng, du lịch sinh thái và điểm du lịch' => 'Group: Community tourism, eco-tourism services and tourist destinations',
    ];

    public function run(): void
    {
        $creatorId = User::withoutGlobalScopes()->where('email', 'haidang.pv@vigiadinh.vn')->value('id')
            ?? User::withoutGlobalScopes()->value('id');

        if (! $creatorId) {
            $this->command->warn('  ⚠ Không tìm thấy user nào — chạy AuthDatabaseSeeder trước.');

            return;
        }

        $created = 0;
        foreach (self::TAXONOMY as $index => $node) {
            $created += $this->seedNode($node, null, 0, $index, $creatorId);
        }

        $this->command->info("  ✓ Danh mục OCOP chuẩn hóa theo spec/danhmuc.html ({$created} danh mục mới).");
    }

    /** @return int Số danh mục vừa tạo (đệ quy cả cây con). */
    private function seedNode(array $node, ?int $parentId, int $depth, int $sortOrder, int $creatorId): int
    {
        $category = OcopCategory::withTrashed()
            ->where('parent_id', $parentId)
            ->where('code', $node['code'])
            ->first();

        $created = 0;

        if (! $category) {
            $category = OcopCategory::create([
                'parent_id' => $parentId,
                'depth' => $depth,
                'name' => $node['name'],
                'slug' => $this->uniqueSlug($node['code'].' '.$node['name']),
                'code' => $node['code'],
                'authority' => $node['authority'] ?? null,
                'sort_order' => $sortOrder,
                'is_active' => true,
                'created_by' => $creatorId,
            ]);
            $created++;
        }

        if (isset(self::EN_NAMES[$node['name']])) {
            $category->translations()->updateOrCreate(['locale' => 'en'], ['name' => self::EN_NAMES[$node['name']]]);
        }

        foreach ($node['children'] ?? [] as $childIndex => $child) {
            $created += $this->seedNode($child, $category->id, $depth + 1, $childIndex, $creatorId);
        }

        return $created;
    }

    private function uniqueSlug(string $seed): string
    {
        $base = Str::slug($seed);
        $slug = $base;
        $i = 2;

        while (OcopCategory::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
