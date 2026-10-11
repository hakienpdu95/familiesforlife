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
        if (Schema::hasTable('notification_preferences')) {
            return;
        }

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('organization_id');
            $table->string('event_type', 100);
            $table->boolean('channel_db')->default(true);
            $table->boolean('channel_mail')->default(false);
            $table->boolean('channel_push')->default(false);
            $table->timestamps();
            

            // Indexes
            $table->unique(['user_id', 'organization_id', 'event_type'], 'notif_pref_user_org_type_unique');
            $table->index(['user_id', 'organization_id']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};