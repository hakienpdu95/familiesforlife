<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('survey_fields')) {
            return;
        }

        Schema::create('survey_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete()->comment('FK -> surveys');
            $table->foreignId('section_id')->nullable()->constrained('survey_sections')->nullOnDelete()->comment('FK -> survey_sections (nullable)');
            $table->foreignId('parent_field_id')->nullable()->constrained('survey_fields')->nullOnDelete()->comment('Self-ref FK — câu hỏi con');
            $table->string('field_key', 100)->comment('Machine key: company_name, ai_tools_used');
            $table->string('label', 500)->comment('Nhãn hiển thị câu hỏi');
            $table->unsignedTinyInteger('field_type')->comment('Loại field (enum số hóa)');
            $table->unsignedTinyInteger('value_kind')->comment('Cột lưu giá trị sẽ dùng');
            $table->boolean('is_required')->default(false)->comment('Bắt buộc điền');
            $table->boolean('is_active')->default(true)->comment('Field đang active');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Thứ tự hiển thị');
            $table->integer('rule_min')->nullable()->comment('Validation: giá trị tối thiểu');
            $table->integer('rule_max')->nullable()->comment('Validation: giá trị tối đa');
            $table->smallInteger('rule_max_select')->nullable()->comment('Giới hạn số lựa chọn multi-choice');
            $table->string('placeholder', 255)->nullable()->comment('Placeholder text');
            $table->timestamps();
            

            // Indexes
            $table->unique(['survey_id', 'field_key']);
            $table->index(['survey_id', 'section_id', 'sort_order']);
            $table->index('parent_field_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_fields');
    }
};