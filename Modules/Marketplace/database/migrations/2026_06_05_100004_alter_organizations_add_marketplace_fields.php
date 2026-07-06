<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds fields needed by Marketplace employer registration flow to the shared organizations table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'source')) {
                $table->string('source', 50)->nullable()->after('status')
                    ->comment('marketplace_signup | admin_created | etc.');
            }
            if (!Schema::hasColumn('organizations', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('source');
            }
            if (!Schema::hasColumn('organizations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['source', 'approved_by', 'approved_at']);
        });
    }
};
