<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('name', 80);
            $table->string('slug', 90);
            $table->char('color_hex', 7)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'slug'], 'uq_kc_tag_org_slug');
            $table->index('organization_id', 'idx_kc_tag_org');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_tags');
    }
};
