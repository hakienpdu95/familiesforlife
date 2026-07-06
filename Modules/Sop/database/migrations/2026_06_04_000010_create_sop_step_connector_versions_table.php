<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop_step_connector_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
            // Dùng position thay vì FK — chỉ cần biết "bước số mấy nối bước số mấy" để render lại
            $table->smallInteger('from_position')->unsigned();
            $table->smallInteger('to_position')->unsigned();
            $table->enum('connector_type', [
                'sequence', 'yes_branch', 'no_branch', 'trigger', 'return', 'exception',
            ]);
            $table->string('label', 60)->nullable();
            $table->char('color_hex', 7)->nullable();

            $table->index('sop_version_id', 'idx_conn_ver_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_step_connector_versions');
    }
};
