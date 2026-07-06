<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thay thế `blueprint_deployment_settings.setting_key='sidebar_config'` (JSON, spec §2.3)
     * bằng bảng quan hệ tường minh, có self-FK parent_id để hỗ trợ submenu lồng nhau —
     * không cần parse JSON để biết thứ tự/nesting của sidebar.
     */
    public function up(): void
    {
        Schema::create('blueprint_sidebar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('blueprint_sidebar_items')->cascadeOnDelete();
            $table->string('module_key', 100); // khớp key sidebar/permission module
            $table->string('label', 255);
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['blueprint_version_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_sidebar_items');
    }
};
