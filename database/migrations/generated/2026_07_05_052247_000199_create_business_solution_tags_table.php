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
        if (Schema::hasTable('business_solution_tags')) {
            return;
        }

        Schema::create('business_solution_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('business_solution_id')->constrained('business_solutions')->cascadeOnDelete();
            $table->string('tag', 100);
            $table->timestamps();
            

            // Indexes
            $table->index(['business_solution_id', 'tag']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('business_solution_tags');
    }
};