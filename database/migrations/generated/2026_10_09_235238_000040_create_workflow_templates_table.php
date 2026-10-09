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
        if (Schema::hasTable('workflow_templates')) {
            return;
        }

        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
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
            $table->unsignedTinyInteger('version')->default(1);
            $table->unsignedInteger('usage_count')->default(0);
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('preview_description')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['category', 'is_public']);
            $table->index('trigger_type');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_templates');
    }
};