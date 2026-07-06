<?php

namespace Modules\Sop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopStep;

/**
 * @extends Factory<SopStep>
 */
class SopStepFactory extends Factory
{
    protected $model = SopStep::class;

    public function definition(): array
    {
        return [
            'uuid'             => Str::uuid(),
            'sop_id'           => SopProcess::factory(),
            'position'         => 1,
            'title'            => fake()->sentence(3),
            'description'      => fake()->sentence(),
            'step_type'        => 'action',
            'duration_minutes' => fake()->numberBetween(5, 60),
            'is_mandatory'     => true,
            'is_active'        => true,
            'created_by'       => 1,
            'updated_by'       => 1,
        ];
    }

    public function start(): static
    {
        return $this->state(['step_type' => 'start', 'position' => 1]);
    }

    public function end(): static
    {
        return $this->state(['step_type' => 'end']);
    }

    public function decision(): static
    {
        return $this->state(['step_type' => 'decision']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
