<?php

namespace Modules\ActivityLog\Contracts;

interface LoggableSubject
{
    public function getActivityLabel(): string;

    public function getActivityRouteUrl(): ?string;
}
