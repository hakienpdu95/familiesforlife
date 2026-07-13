<?php

namespace Modules\Aicem\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Adapter contract mà mỗi module chỉ định (Post, Product, ...) phải cài đặt để AICEM có thể
 * đọc/ghi nội dung của nó mà không cần biết tên cột thật hay cấu trúc block cụ thể — xem
 * spec/AICEM_Technical_Specification.md mục 6.1/6.2. Thêm module chỉ định thứ N chỉ cần viết
 * 1 resolver mới + đăng ký trong config/aicem_subjects.php, không sửa code lõi của AICEM.
 */
interface AicemSubjectResolver
{
    /** Giá trị hiện tại của các field khai báo trong config, keyed theo field code trừu tượng. */
    public function fields(Model $subject): array;

    /**
     * [] nếu subject_type không hỗ trợ block.
     *
     * @return array<int, array{block_id: int, type: string, body: string}>
     */
    public function blocks(Model $subject): array;

    /** Ghi 1 suggestion field đã được accept — phải đi qua Action gốc của module chỉ định, không update() trực tiếp. */
    public function applyFieldSuggestion(Model $subject, string $field, string $suggestedText, int $userId): void;

    /** Ghi 1 suggestion block đã được accept. Không được gọi nếu subject_type không hỗ trợ block. */
    public function applyBlockSuggestion(Model $subject, int $blockId, string $suggestedText, int $userId): void;

    /** Thuộc tính phân loại của CHÍNH instance này, dùng để resolve knowledge document theo scope (mục 6.7). Keyed theo taxonomy_keys khai trong registry. */
    public function taxonomy(Model $subject): array;

    /**
     * Tổ chức mà workflow/knowledge-base/ngân sách AI của subject này áp dụng theo
     * (spec/Platform_RBAC_Phase2_Specification.md §3.4, v3.0) — KHÔNG phải lúc nào cũng bằng
     * `$subject->organization_id` (Post không còn cột này — trả về hằng số tổ chức biên tập
     * nền tảng cố định). Gộp về đúng 1 chỗ thay vì lặp lại logic này ở
     * `ListRunnableWorkflowsHandler`/`AicemGenerationController`/`StartGenerationRunAction`.
     */
    public function organizationId(Model $subject): int;
}
