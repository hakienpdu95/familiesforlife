<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->restrictOnDelete(); // PIN cứng — bắt buộc published
            $table->string('name', 255); // tên riêng trong tổ chức, VD "AI Truy xuất HTX Tiên Dương"
            $table->unsignedBigInteger('owner_id'); // users.id
            $table->string('status', 20)->default('draft'); // OrganizationSolutionStatus
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Tên constraint đặt tường minh — tên tự sinh vượt quá giới hạn 64 ký tự của MySQL.
            $table->unique(['organization_id', 'business_solution_id'], 'org_solutions_org_business_solution_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_solutions');
    }
};
