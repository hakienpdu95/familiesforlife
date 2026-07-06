<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thay thế `blueprint_deployment_settings.setting_key='default_roles'` (JSON, spec §2.3)
     * bằng bảng quan hệ tường minh — mỗi role trừu tượng (field_officer, supervisor, manager...)
     * là 1 dòng, có thể FK/index/query trực tiếp thay vì phải parse JSON blob.
     */
    public function up(): void
    {
        Schema::create('blueprint_deployment_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->string('role_code', 100);  // 'field_officer' | 'supervisor' | 'manager' (trừu tượng)
            $table->string('role_name', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['blueprint_version_id', 'role_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_deployment_roles');
    }
};
