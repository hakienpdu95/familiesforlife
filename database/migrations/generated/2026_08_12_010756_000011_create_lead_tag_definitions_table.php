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
        if (Schema::hasTable('lead_tag_definitions')) {
            return;
        }

        Schema::create('lead_tag_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedInteger('organization_id');
            $table->string('name', 50);
            $table->string('color', 16)->default('gray');
            $table->timestamps();
            

            // Indexes
            $table->unique(['organization_id', 'name'], 'uq_tag_org_name');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tag_definitions');
    }
};