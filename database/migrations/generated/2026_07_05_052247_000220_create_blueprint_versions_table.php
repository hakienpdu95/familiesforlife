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
        if (Schema::hasTable('blueprint_versions')) {
            return;
        }

        Schema::create('blueprint_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('blueprint_id')->constrained('blueprints')->cascadeOnDelete();
            $table->string('version', 30);
            $table->string('status', 20)->default('draft');
            $table->text('release_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->foreignId('parent_version_id')->nullable()->constrained('blueprint_versions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['blueprint_id', 'version']);
            $table->index(['blueprint_id', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_versions');
    }
};