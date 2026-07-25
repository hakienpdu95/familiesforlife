<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12.7 (v1.11) — "rejected_ideas" (Decision Log): ý tưởng đã cân nhắc
 * và QUYẾT ĐỊNH KHÔNG viết, kèm lý do (VD "đối thủ đã làm rất kỹ, khó cạnh tranh") — tham khảo
 * "Five-File Framework" (matthopkins.com) — Decision Log giúp AI không đề xuất lại thứ đã bị bác
 * bỏ. Bổ sung cho `pain_points`/danh sách bài đã publish (§12.8, tự động) — cái này là tribal
 * knowledge editor tự ghi tay, không suy ra được từ dữ liệu có sẵn.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cie_category_foundations', 'rejected_ideas')) {
            return;
        }

        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->text('rejected_ideas')->nullable()->after('pain_points');
        });
    }

    public function down(): void
    {
        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->dropColumn('rejected_ideas');
        });
    }
};
