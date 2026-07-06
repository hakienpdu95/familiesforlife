<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop_relations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            // RESTRICT — không xóa SOP khi còn relation
            $table->foreignId('sop_id')->constrained('sop_processes')->restrictOnDelete();
            $table->foreignId('related_sop_id')->constrained('sop_processes')->restrictOnDelete();
            $table->enum('relation_type', ['prerequisite', 'related', 'replaces', 'replaced_by'])->index();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['sop_id', 'related_sop_id', 'relation_type'], 'idx_sop_rel_unique');
            $table->index('related_sop_id', 'idx_sop_rel_related');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_relations');
    }
};
