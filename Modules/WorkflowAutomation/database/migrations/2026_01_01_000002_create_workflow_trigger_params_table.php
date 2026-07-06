<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_trigger_params', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->string('param_key', 64);
            $table->string('param_value', 255)->nullable();
            $table->tinyInteger('param_type')->unsigned()->default(1);
            $table->index(['workflow_id', 'param_key'], 'idx_trigger_params');
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_trigger_params'); }
};
