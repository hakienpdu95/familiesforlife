<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.5 — giá trị thực tế của tiêu chí theo từng
 * Entity. Cột vật lý riêng theo *kind* (rút gọn từ FieldType/ValueKind của Modules\Survey) thay
 * vì 1 cột `value` đa năng — cho phép filter/sort trực tiếp bằng SQL. Xem §3.5.1/§3.5.2 trước khi
 * sửa bảng này — đặc biệt hành vi multi_select (header row rỗng ở đây + N hàng ở
 * criterion_value_options) và range (value_number = min, value_number_max = max).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterion_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->string('value_string', 255)->nullable();       // text
            $table->decimal('value_number', 18, 4)->nullable();     // number, range (cận dưới)
            $table->decimal('value_number_max', 18, 4)->nullable(); // range (cận trên)
            $table->boolean('value_bool')->nullable();              // boolean
            $table->date('value_date')->nullable();                 // date
            $table->foreignId('option_id')->nullable()               // select (đơn)
                ->constrained('criterion_options')->nullOnDelete();
            // Escape hatch — KHÔNG dùng bởi type nào ở v1.0, chỉ dùng khi có quyết định chính
            // thức thêm 1 CriterionType mới không map được vào cột typed nào ở trên (§3.5.2).
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['entity_id', 'criterion_id'], 'uq_criterion_values_entity_criterion');
            $table->index('criterion_id', 'idx_criterion_values_criterion');
            // Chưa thêm index (criterion_id, value_number)/(criterion_id, value_number_max) ở
            // v1.0 — cân nhắc thêm nếu sau này filter theo range chạy chậm trên tập Entity lớn
            // (§3.5, §11.1).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_values');
    }
};
