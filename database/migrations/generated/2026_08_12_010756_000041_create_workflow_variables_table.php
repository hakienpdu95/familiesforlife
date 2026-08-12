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
        if (Schema::hasTable('workflow_variables')) {
            return;
        }

        Schema::create('workflow_variables', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('workflow_id');
            $table->string('var_key', 64);
            $table->text('var_value')->nullable();
            $table->unsignedTinyInteger('var_type')->default(1);
            $table->string('description', 191)->nullable();
            $table->boolean('is_secret')->default(false);
            

            // Indexes
            $table->unique(['workflow_id', 'var_key'], 'uniq_wf_var');
            $table->index('workflow_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_variables');
    }
};