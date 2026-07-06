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
        if (Schema::hasTable('rc_candidate_attachments')) {
            return;
        }

        Schema::create('rc_candidate_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('candidate_id')->index();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->string('file_type', 20)->default('cv');
            $table->string('file_name', 255);
            $table->text('file_url');
            $table->integer('file_size_kb');
            $table->string('storage_provider', 20)->default('local');
            $table->string('storage_key', 500);
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('uploaded_at')->useCurrent();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_candidate_attachments');
    }
};