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
        if (Schema::hasTable('sop_versions')) {
            return;
        }

        Schema::create('sop_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('sop_id')->constrained('sop_processes')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->index();
            $table->text('change_summary')->nullable();
            $table->unsignedSmallInteger('total_steps')->default(0);
            $table->unsignedInteger('total_duration_minutes')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->unique(['sop_id', 'version_number'], 'idx_version_num');
            $table->index(['sop_id', 'status'], 'idx_version_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_versions');
    }
};