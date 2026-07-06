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
        if (Schema::hasTable('workflow_input_fields')) {
            return;
        }

        Schema::create('workflow_input_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('field_key', 64);
            $table->string('field_label', 128);
            $table->unsignedTinyInteger('field_type')->default(1);
            $table->text('field_options')->nullable();
            $table->string('placeholder', 191)->nullable();
            $table->string('default_value', 255)->nullable();
            $table->string('hint', 255)->nullable();
            $table->boolean('required')->default(false);
            

            // Indexes
            $table->index(['workflow_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_input_fields');
    }
};