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
        if (Schema::hasTable('sop_step_raci')) {
            return;
        }

        Schema::create('sop_step_raci', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('step_id')->constrained('sop_steps')->cascadeOnDelete();
            $table->enum('assignee_type', ['user', 'role'])->default('role');
            $table->unsignedBigInteger('assignee_id')->index();
            $table->enum('raci_type', ['R', 'A', 'C', 'I']);
            $table->text('notes')->nullable();
            

            // Indexes
            $table->unique(['step_id', 'assignee_type', 'assignee_id', 'raci_type'], 'idx_raci_unique');
            $table->index(['assignee_type', 'assignee_id'], 'idx_raci_assignee');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_step_raci');
    }
};