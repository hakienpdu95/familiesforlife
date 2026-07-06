<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assessment\Models\JobTitleDomainRequirement;
use Modules\JobTitle\Models\JobTitle;

class JobTitleDomainRequirementsSeeder extends Seeder
{
    /**
     * Domain requirement scores by job title level tier.
     * Keys: [D1, D2, D3, D4, D5, D6, critical_domains]
     */
    private function getRequirementsForLevel(int $level): array
    {
        return match (true) {
            $level <= 2  => [
                'scores'   => ['D1' => 25, 'D2' => 20, 'D3' => 15, 'D4' => 20, 'D5' => 15, 'D6' => 20],
                'critical' => [],
            ],
            $level <= 5  => [
                'scores'   => ['D1' => 35, 'D2' => 30, 'D3' => 25, 'D4' => 30, 'D5' => 25, 'D6' => 30],
                'critical' => [],
            ],
            $level <= 7  => [
                'scores'   => ['D1' => 50, 'D2' => 45, 'D3' => 45, 'D4' => 50, 'D5' => 40, 'D6' => 45],
                'critical' => ['D3', 'D4'],
            ],
            $level <= 9  => [
                'scores'   => ['D1' => 60, 'D2' => 55, 'D3' => 55, 'D4' => 60, 'D5' => 50, 'D6' => 55],
                'critical' => ['D3', 'D4'],
            ],
            $level <= 11 => [
                'scores'   => ['D1' => 65, 'D2' => 60, 'D3' => 60, 'D4' => 65, 'D5' => 55, 'D6' => 60],
                'critical' => [],
            ],
            $level <= 13 => [
                'scores'   => ['D1' => 70, 'D2' => 68, 'D3' => 68, 'D4' => 70, 'D5' => 65, 'D6' => 68],
                'critical' => [],
            ],
            $level <= 15 => [
                'scores'   => ['D1' => 75, 'D2' => 72, 'D3' => 72, 'D4' => 75, 'D5' => 70, 'D6' => 72],
                'critical' => ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'],
            ],
            $level <= 17 => [
                'scores'   => ['D1' => 80, 'D2' => 78, 'D3' => 78, 'D4' => 80, 'D5' => 75, 'D6' => 78],
                'critical' => ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'],
            ],
            default      => [
                'scores'   => ['D1' => 85, 'D2' => 83, 'D3' => 83, 'D4' => 85, 'D5' => 82, 'D6' => 83],
                'critical' => ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'],
            ],
        };
    }

    public function run(): void
    {
        if (JobTitleDomainRequirement::whereNull('organization_id')->exists()) {
            $this->command->info('JobTitleDomainRequirements (system defaults) already exist, skipping.');
            return;
        }

        $jobTitles = JobTitle::withoutTenant()->get();

        if ($jobTitles->isEmpty()) {
            $this->command->warn('No job titles found. Run JobTitleDatabaseSeeder first.');
            return;
        }

        $rows  = [];
        $now   = now();

        foreach ($jobTitles as $jobTitle) {
            $config = $this->getRequirementsForLevel($jobTitle->level);

            foreach ($config['scores'] as $domainCode => $requiredScore) {
                $rows[] = [
                    'organization_id' => null,
                    'job_title_id'    => $jobTitle->id,
                    'domain_code'     => $domainCode,
                    'required_score'  => $requiredScore,
                    'is_critical'     => in_array($domainCode, $config['critical']) ? 1 : 0,
                    'notes'           => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
        }

        // Insert in chunks to avoid exceeding DB packet size
        foreach (array_chunk($rows, 100) as $chunk) {
            JobTitleDomainRequirement::insert($chunk);
        }

        $this->command->info(
            sprintf(
                'Seeded %d domain requirements for %d job titles.',
                count($rows),
                $jobTitles->count()
            )
        );
    }
}
