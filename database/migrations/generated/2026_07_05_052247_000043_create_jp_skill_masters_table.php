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
        if (Schema::hasTable('jp_skill_masters')) {
            return;
        }

        Schema::create('jp_skill_masters', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete()->comment('NULL = skill hệ thống toàn cục');
            $table->string('name', 100);
            $table->string('slug', 110);
            $table->string('category', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'slug'], 'idx_jp_skill_slug');
            $table->index(['organization_id', 'category', 'is_active'], 'idx_jp_skill_cat');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_skill_masters');
    }
};