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
        if (Schema::hasTable('sandbox_environments')) {
            return;
        }

        Schema::create('sandbox_environments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id')->nullable()->comment('null = global template');
            $table->string('env_code', 50)->unique();
            $table->string('name', 200);
            $table->string('type', 30)->comment('office|data|sales|hr|workflow|leadership');
            $table->unsignedTinyInteger('tier')->default(1)->comment('1=Foundation 2=Practitioner 3=Professional 4=Leader 5=Expert');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['type', 'tier'], 'idx_sandenv_type_tier');
            $table->index('organization_id', 'idx_sandenv_org');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_environments');
    }
};