<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * criteria.is_required chưa từng được đọc ở đâu — required-ness thật sự do
 * entity_type_criterion.is_required (pivot, chỉnh ở màn hình entity-types/{slug}/criteria) quyết
 * định (StoreEntityRequest/UpdateEntityRequest::criterionValueRules() đọc $criterion->pivot->
 * is_required, không phải $criterion->is_required). Cột này chỉ là 1 checkbox "trông giống có
 * tác dụng" trên form tạo/sửa Criterion nhưng không ảnh hưởng gì — xoá thay vì giữ lại gây nhầm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->dropColumn('is_required');
        });
    }

    public function down(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->boolean('is_required')->default(false);
        });
    }
};
