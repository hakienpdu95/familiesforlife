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
        if (Schema::hasTable('aicem_example_candidates')) {
            return;
        }

        Schema::create('aicem_example_candidates', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->string('suggested_title', 255);
            $table->longText('suggested_content');
            $table->json('suggested_scope')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('created_knowledge_document_id')->nullable()->constrained('aicem_knowledge_documents')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'subject_type', 'subject_id'], 'aicem_ec_org_subject_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_example_candidates');
    }
};