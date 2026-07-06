<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->foreignId('sop_id')->constrained('sop_processes')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->index();
            $table->text('change_summary')->nullable();
            $table->smallInteger('total_steps')->unsigned()->default(0);
            $table->unsignedInteger('total_duration_minutes')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['sop_id', 'version_number'], 'idx_version_num');
            $table->index(['sop_id', 'status'], 'idx_version_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_versions');
    }
};
