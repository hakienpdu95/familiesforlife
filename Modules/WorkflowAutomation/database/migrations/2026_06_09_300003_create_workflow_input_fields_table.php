<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_input_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->string('field_key', 64);
            $table->string('field_label', 128);
            $table->tinyInteger('field_type')->unsigned()->default(1);
            $table->text('field_options')->nullable();
            $table->string('placeholder', 191)->nullable();
            $table->string('default_value', 255)->nullable();
            $table->string('hint', 255)->nullable();
            $table->boolean('required')->default(false);
            $table->index(['workflow_id', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_input_fields'); }
};
