<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'organization_id')) {
                $table->foreignId('organization_id')
                      ->nullable()
                      ->after('id')
                      ->constrained()
                      ->restrictOnDelete();
            }

            if (!Schema::hasColumn('assessments', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
