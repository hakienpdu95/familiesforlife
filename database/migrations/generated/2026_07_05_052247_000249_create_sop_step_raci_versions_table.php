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
        if (Schema::hasTable('sop_step_raci_versions')) {
            return;
        }

        Schema::create('sop_step_raci_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
            $table->foreignId('step_version_id')->constrained('sop_step_versions')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_position');
            $table->enum('assignee_type', ['user', 'role']);
            $table->unsignedBigInteger('assignee_id');
            $table->string('assignee_name', 150);
            $table->enum('raci_type', ['R', 'A', 'C', 'I']);
            

            // Indexes
            $table->index('sop_version_id', 'idx_raci_ver_version');
            $table->index('step_version_id', 'idx_raci_ver_step');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_step_raci_versions');
    }
};