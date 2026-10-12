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
        if (Schema::hasTable('workflow_entity_states')) {
            return;
        }

        Schema::create('workflow_entity_states', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('entity_type', 64);
            $table->string('state_key', 64);
            $table->string('state_label', 128);
            $table->string('color', 7)->nullable();
            $table->string('icon', 64)->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            

            // Indexes
            $table->unique(['organization_id', 'entity_type', 'state_key'], 'uniq_entity_state');
            $table->index(['organization_id', 'entity_type', 'sort_order'], 'idx_wes_org_type_order');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_entity_states');
    }
};