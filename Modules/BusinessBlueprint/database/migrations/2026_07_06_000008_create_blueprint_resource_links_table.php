<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_resource_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->foreignId('checklist_id')->nullable()->constrained('blueprint_checklists')->nullOnDelete();
            $table->string('resource_type', 50); // 'sop' | 'knowledge' | 'dataset' | 'template' — polymorphic mềm
            $table->unsignedBigInteger('resource_id'); // trỏ tới sop_processes.id hoặc kc_items.id tuỳ resource_type
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_resource_links');
    }
};
