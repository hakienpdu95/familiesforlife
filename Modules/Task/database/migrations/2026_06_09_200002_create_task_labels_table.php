<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 80);
            $table->char('color_hex', 7)->default('#B4B2A9');
            $table->string('description', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            $table->unique(['project_id', 'name'], 'uq_task_label_name');
            $table->index('organization_id', 'idx_task_labels_org');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_labels');
    }
};
