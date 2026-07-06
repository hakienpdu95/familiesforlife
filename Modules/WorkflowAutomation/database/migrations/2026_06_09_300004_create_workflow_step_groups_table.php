<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_step_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->string('name', 128)->nullable();
            $table->tinyInteger('execute_mode')->unsigned()->default(1);
            // 1=sequential 2=parallel 3=parallel_any
            $table->unsignedSmallInteger('delay_minutes')->default(0);
            $table->boolean('halt_workflow_on_fail')->default(false);
            $table->index(['workflow_id', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('workflow_step_groups'); }
};
