<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->string('action_type', 64);
            // Email
            $table->string('email_to', 500)->nullable();
            $table->string('email_subject', 191)->nullable();
            $table->string('email_template', 128)->nullable();
            // Notification
            $table->string('notif_title', 191)->nullable();
            $table->string('notif_body', 500)->nullable();
            $table->string('notif_target', 128)->nullable();
            // Subject update
            $table->string('update_model', 64)->nullable();
            $table->string('update_field', 64)->nullable();
            $table->string('update_value', 255)->nullable();
            // Webhook
            $table->string('webhook_url', 2000)->nullable();
            $table->tinyInteger('webhook_method')->unsigned()->nullable();
            $table->string('webhook_secret', 128)->nullable();
            // Lead (added when Lead module is built)
            $table->string('lead_status', 64)->nullable();
            $table->string('lead_source', 64)->nullable();
            $table->unsignedBigInteger('lead_assigned_to')->nullable();
            // User (added when User module is built)
            $table->string('user_tag', 64)->nullable();
            $table->string('user_status', 32)->nullable();
            $table->unsignedSmallInteger('delay_minutes')->default(0);
            $table->timestamps();
            $table->index(['workflow_id', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_steps'); }
};
