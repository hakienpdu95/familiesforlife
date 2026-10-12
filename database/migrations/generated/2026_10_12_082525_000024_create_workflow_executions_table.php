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
        if (Schema::hasTable('workflow_executions')) {
            return;
        }

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('organization_id')->index();
            $table->char('run_id', 36)->unique();
            $table->string('trigger_type', 64);
            $table->string('source_module', 64)->nullable();
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedTinyInteger('status');
            $table->string('skip_reason', 64)->nullable();
            $table->boolean('condition_result')->nullable();
            $table->unsignedTinyInteger('steps_total')->default(0);
            $table->unsignedTinyInteger('steps_success')->default(0);
            $table->unsignedTinyInteger('steps_failed')->default(0);
            $table->unsignedTinyInteger('steps_scheduled')->default(0);
            $table->unsignedSmallInteger('duration_ms')->nullable();
            $table->dateTime('triggered_at');
            $table->dateTime('executed_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['workflow_id', 'triggered_at']);
            $table->index(['organization_id', 'triggered_at']);
            $table->index(['status', 'triggered_at']);
            $table->index(['subject_type', 'subject_id', 'triggered_at']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_executions');
    }
};