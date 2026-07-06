<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable(); // FK thêm sau khi blueprint_versions tồn tại
            $table->string('status', 20)->default('draft'); // draft|published|archived — trạng thái tổng quát của "chuỗi version"
            $table->unsignedBigInteger('created_by')->nullable(); // readiness checklist #11 (A04.1 §7.4) — soft ref users.id
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_solution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprints');
    }
};
