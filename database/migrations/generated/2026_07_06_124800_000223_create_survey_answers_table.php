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
        if (Schema::hasTable('survey_answers')) {
            return;
        }

        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('response_id')->constrained('survey_responses')->cascadeOnDelete()->comment('FK -> survey_responses (CASCADE)');
            $table->foreignId('field_id')->constrained('survey_fields')->cascadeOnDelete()->comment('FK -> survey_fields');
            $table->foreignId('option_id')->nullable()->constrained('survey_field_options')->nullOnDelete()->comment('FK -> survey_field_options (nullable)');
            $table->string('value_string', 500)->nullable()->comment('Text ngắn — indexable');
            $table->text('value_text')->nullable()->comment('Textarea dài — không index');
            $table->decimal('value_number', 15, 2)->nullable()->comment('Giá trị số');
            $table->date('value_date')->nullable()->comment('Giá trị ngày');
            $table->boolean('value_bool')->nullable()->comment('Giá trị Có/Không');
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['field_id', 'option_id']);
            $table->index(['field_id', 'value_number']);
            $table->index(['field_id', 'value_bool']);
            $table->index(['field_id', 'value_string']);
            $table->index(['response_id', 'field_id']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};