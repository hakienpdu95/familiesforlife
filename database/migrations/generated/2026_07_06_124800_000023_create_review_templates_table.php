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
        if (Schema::hasTable('review_templates')) {
            return;
        }

        Schema::create('review_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('period_type', 20)->default('quarterly');
            $table->string('apply_to_function', 50)->nullable();
            $table->unsignedTinyInteger('rating_scale')->default(5);
            $table->tinyInteger('is_system')->default(0);
            $table->tinyInteger('is_locked')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['organization_id', 'is_active'], 'idx_review_templates_org');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('review_templates');
    }
};