<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 128)->unique();
            $table->string('description', 500)->nullable();
            $table->string('category', 64);
            $table->string('icon', 64)->nullable();
            $table->string('color', 7)->nullable();
            $table->text('tags')->nullable();
            $table->text('template_config');
            $table->string('trigger_type', 64);
            $table->boolean('is_public')->default(true);
            $table->unsignedBigInteger('author_org_id')->nullable();
            $table->tinyInteger('version')->unsigned()->default(1);
            $table->unsignedInteger('usage_count')->default(0);
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('preview_description')->nullable();
            $table->timestamps();
            $table->index(['category', 'is_public']);
            $table->index('trigger_type');
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_templates'); }
};
