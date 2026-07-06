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
        if (Schema::hasTable('pass_fail_configs')) {
            return;
        }

        Schema::create('pass_fail_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 50)->unique()->comment('FK logic tới assessments.assessment_code');
            $table->decimal('passing_score', 5, 2);
            $table->string('label_pass', 100)->default('Pass');
            $table->string('label_fail', 100)->default('Fail');
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('pass_fail_configs');
    }
};