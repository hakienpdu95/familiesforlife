<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;

class AssessmentDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TdwcfAssessmentSeeder::class,
            FivePillarAssessmentSeeder::class,
            CertificationDefinitionSeeder::class,
            SandboxEnvironmentSeeder::class,
            CareerPathwaySeeder::class,
            JobTitleDomainRequirementsSeeder::class,
            WorkforceProfileSeeder::class,
            WorkforceRoleActivationSeeder::class,   // phải sau WorkforceProfileSeeder
        ]);
    }
}
