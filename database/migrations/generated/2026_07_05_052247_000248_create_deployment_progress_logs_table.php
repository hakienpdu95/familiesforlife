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
        if (Schema::hasTable('deployment_progress_logs')) {
            return;
        }

        Schema::create('deployment_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deployment_target_id')->constrained()->cascadeOnDelete();
            $table->string('phase', 50);
            $table->unsignedTinyInteger('percent')->default(0);
            $table->text('remark')->nullable();
            $table->foreignId('logged_by')->constrained('users');
            $table->timestamp('logged_at')->useCurrent();
            

            // Indexes
            $table->index('deployment_target_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_progress_logs');
    }
};