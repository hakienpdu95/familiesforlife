<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->smallInteger('id', true, true);
            $table->unsignedInteger('organization_id')->nullable();
            $table->boolean('is_global')->default(false);
            $table->string('code', 32);
            $table->string('label', 64);
            $table->string('icon', 32)->nullable();
            $table->string('color', 16)->default('gray');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code'], 'uq_source_org_code');
            $table->index(['organization_id', 'sort_order', 'is_active'], 'idx_source_org_order');
            $table->index(['is_global', 'sort_order', 'is_active'], 'idx_source_global_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
    }
};
