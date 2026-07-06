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
        if (Schema::hasTable('business_solution_versions')) {
            return;
        }

        Schema::create('business_solution_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('business_solution_id')->constrained('business_solutions')->cascadeOnDelete();
            $table->string('version', 30);
            $table->string('status', 20)->default('draft');
            $table->text('release_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['business_solution_id', 'version']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('business_solution_versions');
    }
};