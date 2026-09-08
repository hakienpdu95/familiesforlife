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
        if (Schema::hasTable('workflow_entity_transitions')) {
            return;
        }

        Schema::create('workflow_entity_transitions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('entity_type', 64);
            $table->string('transition_key', 64);
            $table->string('transition_label', 128);
            $table->unsignedBigInteger('from_state_id')->nullable();
            $table->unsignedBigInteger('to_state_id');
            $table->text('allowed_roles')->nullable();
            $table->boolean('requires_comment')->default(false);
            $table->boolean('requires_confirmation')->default(false);
            $table->unsignedBigInteger('triggers_workflow_id')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            

            // Indexes
            $table->unique(['organization_id', 'entity_type', 'transition_key'], 'uniq_entity_trans');
            $table->index(['entity_type', 'from_state_id']);
            $table->index('triggers_workflow_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_entity_transitions');
    }
};