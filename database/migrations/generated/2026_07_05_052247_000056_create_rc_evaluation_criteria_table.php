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
        if (Schema::hasTable('rc_evaluation_criteria')) {
            return;
        }

        Schema::create('rc_evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('evaluation_id')->index();
            $table->string('criterion_name', 100);
            $table->smallInteger('score');
            $table->text('comment')->nullable();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_evaluation_criteria');
    }
};