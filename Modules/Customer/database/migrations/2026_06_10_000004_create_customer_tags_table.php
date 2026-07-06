<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name', 100);
            $table->string('color', 20)->default('#6b7280');
            $table->timestamps();

            $table->unique(['organization_id', 'name'], 'uq_ctag_org_name');
            $table->index('organization_id');
        });

        Schema::create('customer_tag_map', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['customer_id', 'tag_id']);

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('customer_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tag_map');
        Schema::dropIfExists('customer_tags');
    }
};
