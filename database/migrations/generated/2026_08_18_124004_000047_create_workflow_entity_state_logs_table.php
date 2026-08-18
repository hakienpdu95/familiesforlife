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
        if (Schema::hasTable('workflow_entity_state_logs')) {
            return;
        }

        Schema::create('workflow_entity_state_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');
            $table->string('from_state_key', 64)->nullable();
            $table->string('to_state_key', 64);
            $table->string('transition_key', 64)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('comment', 500)->nullable();
            $table->unsignedBigInteger('triggered_execution_id')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['entity_type', 'entity_id', 'created_at'], 'idx_wesl_entity');
            $table->index(['organization_id', 'entity_type', 'to_state_key'], 'idx_wesl_org_type_state');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_entity_state_logs');
    }
};