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
        if (Schema::hasTable('workflow_steps')) {
            return;
        }

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('action_type', 64);
            $table->string('email_to', 500)->nullable();
            $table->string('email_subject', 191)->nullable();
            $table->string('email_template', 128)->nullable();
            $table->string('notif_title', 191)->nullable();
            $table->string('notif_body', 500)->nullable();
            $table->string('notif_target', 128)->nullable();
            $table->string('update_model', 64)->nullable();
            $table->string('update_field', 64)->nullable();
            $table->string('update_value', 255)->nullable();
            $table->string('webhook_url', 2000)->nullable();
            $table->unsignedTinyInteger('webhook_method')->nullable();
            $table->string('webhook_secret', 128)->nullable();
            $table->string('lead_status', 64)->nullable();
            $table->string('lead_source', 64)->nullable();
            $table->unsignedBigInteger('lead_assigned_to')->nullable();
            $table->string('user_tag', 64)->nullable();
            $table->string('user_status', 32)->nullable();
            $table->unsignedSmallInteger('delay_minutes')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['workflow_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};