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
        if (Schema::hasTable('passport_domain_scores')) {
            return;
        }

        Schema::create('passport_domain_scores', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('passport_entry_id')->constrained('passport_entries')->cascadeOnDelete();
            $table->string('domain_code', 10)->comment('D1|D2|D3|D4|D5|D6');
            $table->string('domain_name', 100)->comment('Tên domain tại thời điểm snapshot');
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('required_score', 5, 2)->nullable()->comment('Yêu cầu của job title tại thời điểm exit');
            $table->decimal('gap', 6, 2)->nullable()->comment('score - required_score (âm = thiếu)');
            $table->tinyInteger('is_critical')->default(0);
            

            // Indexes
            $table->unique(['passport_entry_id', 'domain_code'], 'pds_entry_domain_unique');
            $table->index('passport_entry_id', 'pds_entry_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_domain_scores');
    }
};