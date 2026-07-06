<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop_approval_flows', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
            $table->tinyInteger('step_order')->unsigned()->default(1);
            $table->string('required_role', 100)->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['approved', 'rejected', 'forwarded'])->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['sop_version_id', 'step_order'], 'idx_approval_version');
            $table->index('approver_id', 'idx_approval_approver');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_approval_flows');
    }
};
