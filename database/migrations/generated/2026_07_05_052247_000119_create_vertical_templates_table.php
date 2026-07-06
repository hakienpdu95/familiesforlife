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
        if (Schema::hasTable('vertical_templates')) {
            return;
        }

        Schema::create('vertical_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->string('target_label', 50)->default('Tổ chức');
            $table->string('target_org_category', 30)->default('organization');
            $table->boolean('has_physical_assets')->default(true);
            $table->string('export_adapter', 200)->nullable();
            $table->string('readiness_template_slug', 100)->nullable();
            $table->json('phases');
            $table->json('default_checklist');
            $table->json('default_activity_types')->nullable();
            $table->json('default_legal_doc_types')->nullable();
            $table->json('default_hierarchy');
            $table->json('default_roles');
            $table->json('sidebar_config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            

            // Indexes
            $table->index('is_active');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('vertical_templates');
    }
};