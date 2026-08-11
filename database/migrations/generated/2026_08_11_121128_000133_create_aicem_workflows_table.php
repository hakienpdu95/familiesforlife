<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aicem_workflows')) {
            return;
        }

        Schema::create('aicem_workflows', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('subject_type', 50);
            $table->string('slug', 50);
            $table->string('name', 255);
            $table->text('prompt_template');
            $table->json('filters')->nullable();
            $table->foreignId('context_template_id')->constrained('aicem_context_templates')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['organization_id', 'subject_type', 'slug'], 'aicem_wf_org_subject_slug_unique');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_workflows');
    }
};
