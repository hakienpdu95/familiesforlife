<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_variables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->string('var_key', 64);
            $table->text('var_value')->nullable();
            $table->tinyInteger('var_type')->unsigned()->default(1);
            $table->string('description', 191)->nullable();
            $table->boolean('is_secret')->default(false);
            $table->unique(['workflow_id', 'var_key'], 'uniq_wf_var');
            $table->index('workflow_id');
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_variables'); }
};
