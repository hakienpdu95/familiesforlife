<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop_step_raci_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
            $table->foreignId('step_version_id')->constrained('sop_step_versions')->cascadeOnDelete();
            $table->smallInteger('step_position')->unsigned();
            $table->enum('assignee_type', ['user', 'role']);
            $table->unsignedBigInteger('assignee_id'); // Snapshot BIGINT — không FK
            $table->string('assignee_name', 150);      // Snapshot tên — hiển thị khi user/role bị xóa
            $table->enum('raci_type', ['R', 'A', 'C', 'I']);

            $table->index('sop_version_id', 'idx_raci_ver_version');
            $table->index('step_version_id', 'idx_raci_ver_step');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_step_raci_versions');
    }
};
