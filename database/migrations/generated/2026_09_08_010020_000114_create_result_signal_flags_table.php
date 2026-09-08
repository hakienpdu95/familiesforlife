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
        if (Schema::hasTable('result_signal_flags')) {
            return;
        }

        Schema::create('result_signal_flags', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('result_id')->constrained('assessment_results')->cascadeOnDelete();
            $table->string('flag_code', 100)->comment('e.g. HAS_CRM');
            $table->boolean('flag_value');
            $table->timestamps();
            

            // Indexes
            $table->unique(['result_id', 'flag_code']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('result_signal_flags');
    }
};