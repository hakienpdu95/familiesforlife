<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop_step_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->foreignId('step_id')->constrained('sop_steps')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->text('file_url');
            $table->string('file_type', 50);
            $table->unsignedInteger('file_size_kb');
            $table->string('storage_provider', 20)->default('s3');
            $table->string('storage_key', 500);
            $table->string('alt_text', 300)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();

            $table->index(['step_id', 'sort_order'], 'idx_attachment_step');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_step_attachments');
    }
};
