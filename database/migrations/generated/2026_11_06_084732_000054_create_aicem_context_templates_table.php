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
        if (Schema::hasTable('aicem_context_templates')) {
            return;
        }

        Schema::create('aicem_context_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('subject_type', 50);
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_default')->default(false);
            $table->json('schema');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'subject_type', 'slug'], 'aicem_ct_org_subject_slug_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_context_templates');
    }
};