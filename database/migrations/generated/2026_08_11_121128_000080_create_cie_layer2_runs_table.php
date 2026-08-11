<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cie_layer2_runs')) {
            return;
        }

        Schema::create('cie_layer2_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->decimal('cost_usd', 10, 6);
            $table->string('model_used', 100);
            $table->timestamp('created_at')->nullable();

            // Indexes
            $table->index(['organization_id', 'created_at'], 'cie_layer2_runs_org_created_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('cie_layer2_runs');
    }
};
