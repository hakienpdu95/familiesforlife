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
        if (Schema::hasTable('aicem_suggestions')) {
            return;
        }

        Schema::create('aicem_suggestions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('generation_run_id')->constrained('aicem_generation_runs')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('field', 100)->nullable();
            $table->unsignedBigInteger('block_id')->nullable();
            $table->longText('original_text');
            $table->longText('suggested_text');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['generation_run_id', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_suggestions');
    }
};