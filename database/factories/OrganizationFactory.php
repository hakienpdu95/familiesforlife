<?php

namespace Database\Factories;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company();
        return [
            'name'     => $name,
            'status'   => 'active',
            'owner_id' => User::factory(),
            'settings' => null,
        ];
    }
}
