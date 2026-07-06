<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_chart_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('view_type', 20)->default('tree');
            $table->string('group_by', 20)->default('department');
            $table->foreignId('scope_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->tinyInteger('show_avatar')->default(1);
            $table->tinyInteger('show_job_title')->default(1);
            $table->tinyInteger('show_employee_code')->default(0);
            $table->tinyInteger('show_department')->default(1);
            $table->tinyInteger('show_branch')->default(0);
            $table->unsignedTinyInteger('max_depth')->default(5);
            $table->tinyInteger('expand_by_default')->default(0);
            $table->tinyInteger('is_default')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Generated column: default_lock = organization_id when is_default=1, else NULL
        // Unique index ignores NULL → each org has exactly 1 default config
        // VIRTUAL (not STORED) to allow FK on organization_id in MySQL 8
        DB::statement('ALTER TABLE org_chart_configs ADD COLUMN default_lock BIGINT UNSIGNED AS (IF(is_default = 1, organization_id, NULL)) VIRTUAL');
        DB::statement('ALTER TABLE org_chart_configs ADD UNIQUE KEY uq_default_config (default_lock)');

        DB::statement("ALTER TABLE org_chart_configs ADD CONSTRAINT chk_occ_view_type CHECK (view_type IN ('tree','flat_list','matrix'))");
        DB::statement("ALTER TABLE org_chart_configs ADD CONSTRAINT chk_occ_group_by CHECK (group_by IN ('department','branch','job_title','manager'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('org_chart_configs');
    }
};
