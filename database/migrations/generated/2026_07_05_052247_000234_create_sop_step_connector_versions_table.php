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
        if (Schema::hasTable('sop_step_connector_versions')) {
            return;
        }

        Schema::create('sop_step_connector_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
            $table->unsignedSmallInteger('from_position');
            $table->unsignedSmallInteger('to_position');
            $table->enum('connector_type', ['sequence', 'yes_branch', 'no_branch', 'trigger', 'return', 'exception']);
            $table->string('label', 60)->nullable();
            $table->char('color_hex', 7)->nullable();
            

            // Indexes
            $table->index('sop_version_id', 'idx_conn_ver_version');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_step_connector_versions');
    }
};