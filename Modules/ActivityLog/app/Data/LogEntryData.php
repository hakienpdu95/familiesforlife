<?php

namespace Modules\ActivityLog\Data;

use Modules\ActivityLog\Enums\ActorType;
use Modules\ActivityLog\Enums\LogLevel;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class LogEntryData extends Data
{
    public function __construct(
        // Tenant
        public readonly ?int      $organizationId,

        // Actor
        public readonly ?int      $actorId,
        #[WithCast(EnumCast::class)]
        public readonly ActorType $actorType,
        public readonly string    $actorName,
        public readonly ?string   $actorIp,

        // Action
        public readonly string   $module,
        public readonly string   $action,
        #[WithCast(EnumCast::class)]
        public readonly LogLevel $level,

        // Subject
        public readonly ?string $subjectType,
        public readonly ?int    $subjectId,
        public readonly ?string $subjectLabel,

        // Misc
        public readonly ?string $description,
        public readonly string  $requestId,
        public readonly ?string $sessionId,

        // Typed context
        public readonly array $context,

        // HTTP — nullable vì CLI/Job không có
        public readonly ?HttpSnapshotData $http,

        public readonly \DateTimeImmutable $loggedAt,
    ) {}
}
