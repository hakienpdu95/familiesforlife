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
        if (Schema::hasTable('passport_certifications')) {
            return;
        }

        Schema::create('passport_certifications', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('passport_entry_id')->constrained('passport_entries')->cascadeOnDelete();
            $table->unsignedBigInteger('cert_definition_id')->nullable()->comment('FK certification_definitions — nullable nếu def bị xóa');
            $table->string('cert_code', 50)->comment('Lưu code để bất biến kể cả khi def thay đổi');
            $table->string('cert_name', 200);
            $table->string('cert_type_code', 30)->comment('AI_SALES | AI_HR | ...');
            $table->string('level_code', 30)->comment('FOUNDATION | PRACTITIONER | ...');
            $table->unsignedTinyInteger('level_order');
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->string('certificate_number', 50)->nullable();
            $table->decimal('composite_score_at_issue', 5, 2)->nullable();
            

            // Indexes
            $table->index('passport_entry_id', 'pc_entry_index');
            $table->index('cert_code', 'pc_cert_code_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_certifications');
    }
};