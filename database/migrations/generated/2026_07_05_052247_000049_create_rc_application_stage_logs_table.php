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
        if (Schema::hasTable('rc_application_stage_logs')) {
            return;
        }

        Schema::create('rc_application_stage_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('application_id')->index();
            $table->unsignedBigInteger('stage_id');
            $table->string('result', 20);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('actioned_by');
            $table->timestamp('actioned_at')->useCurrent()->index();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_application_stage_logs');
    }
};