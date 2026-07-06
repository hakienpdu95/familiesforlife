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
        if (Schema::hasTable('deployment_config_snapshot_items')) {
            return;
        }

        Schema::create('deployment_config_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('deployment_snapshot_id')->constrained('deployment_snapshots')->cascadeOnDelete();
            $table->string('configurable_type', 100);
            $table->unsignedBigInteger('configurable_id');
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index('deployment_snapshot_id');
            $table->index(['configurable_type', 'configurable_id'], 'deploy_config_snapshot_items_configurable_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_config_snapshot_items');
    }
};