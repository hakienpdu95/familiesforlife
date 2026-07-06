<?php

namespace Modules\Organization\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Organization\Data\Requests\OrganizationSettingsData;
use Modules\Organization\Models\Organization;
use Spatie\Permission\PermissionRegistrar;

/**
 * Upsert organization settings key/value pairs.
 *
 * Usage: UpdateOrganizationSettingsAction::run($data, $org)
 */
class UpdateOrganizationSettingsAction
{
    use AsAction;

    public function handle(OrganizationSettingsData $data, Organization $org): Organization
    {
        foreach ($data->settings as $key => $value) {
            // Infer the type from the PHP value
            $type = match (true) {
                is_bool($value)    => 'boolean',
                is_int($value)     => 'integer',
                is_float($value)   => 'float',
                is_array($value)   => 'json',
                default            => 'string',
            };

            $org->setSetting($key, $value, $type);
        }

        // Clear permission cache in case settings affect roles/permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $org->fresh();
    }
}
