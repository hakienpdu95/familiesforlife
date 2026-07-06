<?php

namespace Modules\ActivityLog\Core;

use App\Shared\Tenancy\TenantContext;
use Modules\ActivityLog\Data\HttpSnapshotData;
use Modules\ActivityLog\Data\LogEntryData;
use Modules\ActivityLog\Enums\ActorType;
use Modules\ActivityLog\Enums\LogLevel;

final class LogEntryBuilder
{
    private const SENSITIVE = [
        'password', 'token', 'secret', 'token_encrypted',
        'api_key', 'credit_card', 'cvv', 'respondent_ip',
    ];

    public function build(
        string   $module,
        string   $action,
        mixed    $subject,
        array    $context,
        LogLevel $level,
        ?string  $description,
    ): LogEntryData {
        return new LogEntryData(
            organizationId: $this->organizationId(),
            actorId:        $this->actorId(),
            actorType:      $this->actorType(),
            actorName:      $this->actorName(),
            actorIp:        $this->actorIp(),
            module:         $module,
            action:         $action,
            level:          $level,
            subjectType:    $this->subjectType($subject),
            subjectId:      $this->subjectId($subject),
            subjectLabel:   $this->subjectLabel($subject),
            description:    $description ? substr($description, 0, 500) : null,
            requestId:      request()->header('X-Request-Id', (string) \Str::uuid()),
            sessionId:      $this->sessionId(),
            context:        $this->sanitize($context),
            http:           app()->runningInConsole() ? null : HttpSnapshotData::fromRequest(request()),
            loggedAt:       new \DateTimeImmutable(),
        );
    }

    private function organizationId(): ?int
    {
        return TenantContext::isSet() ? TenantContext::getOrganizationId() : null;
    }

    private function actorId(): ?int
    {
        return auth()->id();
    }

    private function actorType(): ActorType
    {
        if (app()->runningInConsole()) return ActorType::System;
        if (auth()->check())           return ActorType::User;
        if (request()->bearerToken())  return ActorType::ApiToken;
        return ActorType::Guest;
    }

    private function actorName(): string
    {
        if (app()->runningInConsole()) return 'system';
        $u = auth()->user();
        if ($u) return $u->name ?? $u->email ?? "User#{$u->id}";
        return 'guest';
    }

    private function actorIp(): ?string
    {
        if (app()->runningInConsole()) return null;
        return request()->ip() ?: null;
    }

    private function subjectType(mixed $s): ?string
    {
        return $s instanceof \Illuminate\Database\Eloquent\Model ? get_class($s) : null;
    }

    private function subjectId(mixed $s): ?int
    {
        return $s instanceof \Illuminate\Database\Eloquent\Model ? (int) $s->getKey() : null;
    }

    private function subjectLabel(mixed $s): ?string
    {
        if (!($s instanceof \Illuminate\Database\Eloquent\Model)) return null;

        if (method_exists($s, 'getActivityLabel')) {
            return substr($s->getActivityLabel(), 0, 255);
        }

        $label = $s->name ?? $s->title ?? $s->email ?? "#{$s->getKey()}";
        return substr((string) $label, 0, 255);
    }

    private function sessionId(): ?string
    {
        try {
            return session()->getId();
        } catch (\Throwable) {
            return null;
        }
    }

    private function sanitize(array $ctx): array
    {
        array_walk_recursive($ctx, function (&$v, $k) {
            if (in_array(strtolower((string) $k), self::SENSITIVE, true)) {
                $v = '[REDACTED]';
            }
        });
        return $ctx;
    }
}
