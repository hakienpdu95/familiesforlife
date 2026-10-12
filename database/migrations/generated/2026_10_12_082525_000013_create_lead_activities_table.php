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
        if (Schema::hasTable('lead_activities')) {
            return;
        }

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedInteger('organization_id');
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
            

            // Indexes
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