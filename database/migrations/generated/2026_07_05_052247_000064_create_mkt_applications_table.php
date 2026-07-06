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
        if (Schema::hasTable('mkt_applications')) {
            return;
        }

        Schema::create('mkt_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('applicant_id');
            $table->string('status', 20)->default('submitted');
            $table->text('cover_letter')->nullable();
            $table->decimal('expected_salary', 15, 2)->nullable();
            $table->date('available_from')->nullable();
            $table->string('portfolio_url', 300)->nullable();
            $table->string('import_status', 20)->default('not_imported');
            $table->char('imported_rc_candidate_id', 36)->nullable();
            $table->char('imported_rc_application_id', 36)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_applications');
    }
};