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

    public function run(): void
    {
        $creatorId = User::withoutGlobalScopes()->where('email', 'content-creator@system.local')->value('id')
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
                'parent_id'  => $parentId,
                'depth'      => $depth,
                'name'       => $node['name'],
                'slug'       => $this->uniqueSlug($node['code'] . ' ' . $node['name']),
                'code'       => $node['code'],
                'authority'  => $node['authority'] ?? null,
                'sort_order' => $sortOrder,
                'is_active'  => true,
                'created_by' => $creatorId,
            ]);
            $created++;
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
        $i    = 2;

        while (OcopCategory::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
