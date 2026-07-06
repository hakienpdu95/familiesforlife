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
        if (Schema::hasTable('org_chart_configs')) {
            return;
        }

        Schema::create('org_chart_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
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

        
    }

    public function down(): void
    {
        Schema::dropIfExists('org_chart_configs');
    }
};