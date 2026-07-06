<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('name', 191);
            $table->string('description', 500)->nullable();
            $table->string('trigger_type', 64);
            $table->tinyInteger('condition_match')->unsigned()->default(1);
            $table->tinyInteger('cooldown_type')->unsigned()->default(0);
            $table->boolean('is_active')->default(false);
            $table->tinyInteger('priority')->unsigned()->default(5);
            $table->unsignedInteger('run_count')->default(0);
            $table->dateTime('last_run_at')->nullable();
            $table->tinyInteger('last_run_status')->unsigned()->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'trigger_type', 'is_active'], 'idx_org_trigger');
            $table->index(['organization_id', 'is_active', 'priority'], 'idx_org_priority');
        });
    }
    public function down(): void { Schema::dropIfExists('workflows'); }
};
