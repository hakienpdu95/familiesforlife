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
        if (Schema::hasTable('deployment_checklist_items')) {
            return;
        }

        Schema::create('deployment_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deployment_target_id')->constrained()->cascadeOnDelete();
            $table->string('phase', 50);
            $table->string('item_key', 100);
            $table->string('item_label', 255);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_done')->default(false);
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->text('notes')->nullable();
            

            // Indexes
            $table->index(['deployment_target_id', 'phase']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_checklist_items');
    }
};