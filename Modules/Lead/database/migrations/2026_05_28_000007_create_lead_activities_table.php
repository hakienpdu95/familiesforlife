<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedInteger('organization_id');

            // type: 1=call 2=email 3=meeting 4=note 5=stage_change
            //       6=assign 7=score_update 8=system 9=task 10=visit
            $table->unsignedTinyInteger('type');
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('outcome', 64)->nullable();

            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('attendee_count')->nullable();

            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 191)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['lead_id', 'created_at'], 'idx_activity_lead');
            $table->index(['organization_id', 'type', 'created_at'], 'idx_activity_org_type');
            $table->index(['scheduled_at', 'completed_at'], 'idx_activity_scheduled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
