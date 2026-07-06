<?php

namespace Modules\Sop\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Sop\Models\SopProcess;

/**
 * @extends Factory<SopProcess>
 */
class SopProcessFactory extends Factory
{
    protected $model = SopProcess::class;

    public function definition(): array
    {
        return [
            'uuid'            => Str::uuid(),
            'organization_id' => 1,
            'owner_id'        => User::factory(),
            'code'            => 'SOP-' . strtoupper(Str::random(6)),
            'title'           => fake()->sentence(4),
            'description'     => fake()->paragraph(),
            'type'            => 'internal',
            'status'          => 'draft',
            'version'         => 0,
            'created_by'      => 1,
            'updated_by'      => 1,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved', 'version' => 1]);
    }

    public function pendingReview(): static
    {
        return $this->state(['status' => 'pending_review']);
    }
}
