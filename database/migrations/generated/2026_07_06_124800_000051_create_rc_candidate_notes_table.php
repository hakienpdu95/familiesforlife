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
        if (Schema::hasTable('rc_candidate_notes')) {
            return;
        }

        Schema::create('rc_candidate_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('candidate_id')->index();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->text('content');
            $table->string('note_type', 30)->default('general');
            $table->boolean('is_private')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_candidate_notes');
    }
};