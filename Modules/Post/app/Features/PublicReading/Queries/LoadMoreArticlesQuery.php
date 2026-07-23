<?php

namespace Modules\Post\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

/**
 * "Xem thêm bài viết" (trang chủ, khối lưới cuối cùng trước footer) — keyset/cursor pagination
 * (afterPublishedAt/afterId), KHÔNG dùng offset hay danh sách loại trừ phình dần theo số lần
 * bấm. 2 lý do hiệu năng:
 *   1. `WHERE (published_at, id) < (?, ?)` tận dụng trực tiếp index
 *      idx_post_trans_status_pub (locale, status, published_at) — chi phí mỗi lần "Xem thêm"
 *      luôn ~O(log n) bất kể đã tải bao nhiêu trang trước đó.
 *   2. offset/whereNotIn(mảng id phình dần) đều là anti-pattern kinh điển: offset buộc DB quét
 *      bỏ qua N dòng đầu (chậm dần theo N), còn whereNotIn với mảng vài trăm id vừa nặng cho
 *      DB vừa tăng dần kích thước request/response qua mỗi lần bấm.
 *
 * excludeArticleIds CHỈ dùng cho phần cố định, KHÔNG phình theo số lần bấm — bài hero +
 * feature chunks (tối đa 7 id) đã hiển thị trước khối lưới, loại 1 lần duy nhất; các trang sau
 * đã nằm sau cursor nên tự động không lặp lại, không cần thêm vào exclude.
 */
class LoadMoreArticlesQuery implements QueryInterface
{
    public function __construct(
        public readonly string $locale,
        public readonly ?string $afterPublishedAt = null,
        public readonly ?int $afterId = null,
        /** @var int[] */
        public readonly array $excludeArticleIds = [],
        public readonly int $limit = 8,
        // spec: "Xem thêm bài viết" theo danh mục (danh-muc/{slug}) — tái dùng đúng query này
        // (cùng cursor/limit) thay vì viết riêng, chỉ thêm 1 điều kiện lọc category khi có.
        public readonly ?int $categoryId = null,
    ) {}
}
