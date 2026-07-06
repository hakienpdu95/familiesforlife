<?php

namespace Modules\Sop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopStep;
use Modules\Sop\Models\SopStepConnector;

/**
 * @extends Factory<SopStepConnector>
 */
class SopStepConnectorFactory extends Factory
{
    protected $model = SopStepConnector::class;

    public function definition(): array
    {
        return [
            'uuid'           => Str::uuid(),
            'sop_id'         => SopProcess::factory(),
            'from_step_id'   => SopStep::factory(),
            'to_step_id'     => SopStep::factory(),
            'connector_type' => 'sequence',
            'label'          => null,
            'color_hex'      => null,
            'sort_order'     => 0,
        ];
    }
}
