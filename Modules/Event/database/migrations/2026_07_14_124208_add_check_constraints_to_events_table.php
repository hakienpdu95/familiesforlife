<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Event_Management_Technical_Specification.md §5.2/§5.5 — "Trường hợp 3" của
 * docs/migration-guide.md (render_migration_file.json không biểu diễn được CHECK constraint).
 *
 * Lớp phòng thủ THỨ HAI (defense-in-depth), KHÔNG thay thế validate ở FormRequest/Action §5.5
 * — chỉ chặn dữ liệu bẩn nếu tầng Action bị bypass (import tay, sửa trực tiếp DB, bug code).
 * Yêu cầu MySQL 8.0.16+ (CHECK constraint được enforce thật, không chỉ parse rồi bỏ qua).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard — thứ tự chạy migration trong môi trường dùng snapshot đã consolidate
        // (database/migrations/generated + extensions) có thể đưa migration này lên TRƯỚC
        // migration tạo bảng `events`. Bảng chưa tồn tại thì không có gì để thêm CHECK.
        if (! Schema::hasTable('events')) {
            return;
        }

        if (! $this->constraintExists('chk_events_physical_fields')) {
            DB::statement("
                ALTER TABLE events ADD CONSTRAINT chk_events_physical_fields CHECK (
                    location_type <> 'physical'
                    OR (venue_name IS NOT NULL AND venue_address IS NOT NULL AND province_code IS NOT NULL)
                )
            ");
        }

        if (! $this->constraintExists('chk_events_price_range')) {
            DB::statement("
                ALTER TABLE events ADD CONSTRAINT chk_events_price_range CHECK (
                    price_type <> 'range' OR (price_min IS NOT NULL AND price_max IS NOT NULL AND price_max >= price_min)
                )
            ");
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE events DROP CONSTRAINT IF EXISTS chk_events_physical_fields');
        DB::statement('ALTER TABLE events DROP CONSTRAINT IF EXISTS chk_events_price_range');
    }

    /** Guard idempotent — cùng nguyên tắc Schema::hasTable() của template chuẩn (migration:generate). */
    private function constraintExists(string $name): bool
    {
        if (! Schema::hasTable('events')) {
            return false;
        }

        $result = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND CONSTRAINT_NAME = ?",
            [$name]
        );

        return $result && $result->cnt > 0;
    }
};
