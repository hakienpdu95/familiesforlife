<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_stage_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedInteger('organization_id');

            $table->unsignedSmallInteger('stage_from_id')->nullable();
            $table->unsignedSmallInteger('stage_to_id');
            $table->string('stage_from_label', 64)->nullable();
            $table->string('stage_to_label', 64);

            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('changed_by_name', 191)->nullable();
            $table->string('note', 500)->nullable();
            $table->dateTime('changed_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['lead_id', 'changed_at'], 'idx_stage_history_lead');
            $table->index(['organization_id', 'changed_at'], 'idx_stage_history_org');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_stage_history');
    }
};
