<?php

namespace App\Shared\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Wrapping\WrapExecutionType;

/**
 * Base DTO for all data transfer objects in the system.
 *
 * Extends spatie/laravel-data and adds shared behavior:
 *  - No default wrapping (API responses control their own envelope)
 *  - Provides toCommand() helper for CQRS pipelines
 *
 * Usage:
 *   class CreateLeadData extends BaseData
 *   {
 *       public function __construct(
 *           public readonly string $name,
 *           public readonly string $email,
 *           public readonly ?string $phone = null,
 *       ) {}
 *   }
 *
 *   $data = CreateLeadData::from($request);
 *   CreateLead::run($data);
 */
abstract class BaseData extends Data
{
    // Spatie Data defaults are sufficient. Override in subclasses as needed.
}
