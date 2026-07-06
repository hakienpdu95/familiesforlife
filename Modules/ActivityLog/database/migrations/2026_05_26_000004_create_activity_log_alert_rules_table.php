<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('module', 64)->nullable();
            $table->string('action', 128)->nullable();
            $table->unsignedTinyInteger('level_min')->nullable();
            $table->unsignedTinyInteger('condition_type')
                  ->comment('1=first_occurrence 2=count_threshold');
            $table->unsignedSmallInteger('threshold_count')->nullable();
            $table->unsignedSmallInteger('window_minutes')->nullable();
            $table->unsignedTinyInteger('notify_channel')
                  ->comment('1=email 2=database');
            $table->string('notify_target', 500);
            $table->unsignedSmallInteger('cooldown_minutes')->default(60);
            $table->dateTime('last_triggered_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'module', 'action'], 'idx_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log_alert_rules');
    }
};
