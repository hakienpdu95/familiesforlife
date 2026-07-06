<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_entity_transitions', function (Blueprint $table) {
            $table->id();
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
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->unique(['organization_id', 'entity_type', 'transition_key'], 'uniq_entity_trans');
            $table->index(['entity_type', 'from_state_id']);
            $table->index('triggers_workflow_id');
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_entity_transitions'); }
};
