<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // spec/bhxh/PensionCalculator_Technical_Specification.md §8.2 — 1 dòng = 1 giai đoạn
        // hiệu lực tham số BHXH tự nguyện. Bất biến sau khi tạo (§9.1) — không có cột updated
        // ngoài Eloquent timestamps mặc định, không có route edit/destroy.
        Schema::create('pension_parameter_periods', function (Blueprint $table) {
            $table->id();
            $table->date('effective_from')->unique();
            $table->decimal('rural_poverty_line', 15, 2);
            $table->decimal('reference_level', 15, 2);
            $table->decimal('contribution_rate_percent', 5, 2)->default(22.00);
            $table->unsignedTinyInteger('ceiling_multiplier')->default(20);
            $table->string('source_document');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pension_parameter_periods');
    }
};
