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
        if (Schema::hasTable('open_assessment_campaigns')) {
            return;
        }

        Schema::create('open_assessment_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('target_job_title_id')->nullable();
            $table->string('target_job_title_label', 200)->nullable()->comment('Bất biến kể cả khi job title đổi');
            $table->string('target_department_label', 200)->nullable();
            $table->unsignedTinyInteger('min_trust_level')->default(2);
            $table->decimal('min_tdwcf_score', 5, 2)->nullable();
            $table->string('status', 20)->default('draft')->comment('draft | open | closed | archived');
            $table->timestamp('open_from')->nullable();
            $table->timestamp('open_until')->nullable();
            $table->unsignedSmallInteger('max_participants')->nullable();
            $table->tinyInteger('is_anonymous_to_org')->default(1)->comment('1=Org không thấy tên cho đến khi invite');
            $table->unsignedSmallInteger('participants_count')->default(0);
            $table->unsignedSmallInteger('completed_count')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index('organization_id', 'oac_org_index');
            $table->index('status', 'oac_status_index');
            $table->index('open_until', 'oac_open_until_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('open_assessment_campaigns');
    }
};