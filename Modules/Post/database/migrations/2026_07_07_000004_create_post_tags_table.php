<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->timestamps();

            $table->unique(['organization_id', 'slug'], 'uq_post_tag_org_slug');
        });

        Schema::create('post_article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('post_tags')->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_tag');
        Schema::dropIfExists('post_tags');
    }
};
