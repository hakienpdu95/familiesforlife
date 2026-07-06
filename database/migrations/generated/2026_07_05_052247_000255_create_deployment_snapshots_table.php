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
        if (Schema::hasTable('deployment_snapshots')) {
            return;
        }

        Schema::create('deployment_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->string('snapshot_type', 50);
            $table->foreignId('blueprint_version_id')->nullable()->constrained('blueprint_versions')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['deployment_id', 'snapshot_type']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_snapshots');
    }
};