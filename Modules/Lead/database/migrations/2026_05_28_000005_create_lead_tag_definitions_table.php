<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_tag_definitions', function (Blueprint $table) {
            $table->smallInteger('id', true, true);
            $table->unsignedInteger('organization_id');
            $table->string('name', 50);
            $table->string('color', 16)->default('gray');
            $table->timestamps();

            $table->unique(['organization_id', 'name'], 'uq_tag_org_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tag_definitions');
    }
};
