<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'aicem_content_vertical')) {
                $table->string('aicem_content_vertical', 50)->nullable()->after('industry')->comment('Key tra config/aicem_content_verticals.php, NULL = dùng generic');
            }
            if (!Schema::hasColumn('organizations', 'ai_provider_config')) {
                $table->text('ai_provider_config')->nullable()->after('aicem_content_vertical')->comment('BYOK: provider + API key riêng của Organization (encrypted cast), NULL = dùng config(ai.default)');
            }
            if (!Schema::hasColumn('organizations', 'ai_monthly_budget_usd')) {
                $table->decimal('ai_monthly_budget_usd', 10, 2)->nullable()->after('ai_provider_config')->comment('Hạn mức chi phí AI/tháng, NULL = không giới hạn');
            }
            if (!Schema::hasColumn('organizations', 'ai_rate_limit_override')) {
                $table->json('ai_rate_limit_override')->nullable()->after('ai_monthly_budget_usd')->comment('Override config(aicem.rate_limit) cho Organization này, NULL = dùng mặc định');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $cols = array_filter(['aicem_content_vertical', 'ai_provider_config', 'ai_monthly_budget_usd', 'ai_rate_limit_override'], fn($c) => Schema::hasColumn('organizations', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};