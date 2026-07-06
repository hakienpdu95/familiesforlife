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
        if (Schema::hasTable('blueprints')) {
            return;
        }

        Schema::create('blueprints', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['business_solution_id', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprints');
    }
};