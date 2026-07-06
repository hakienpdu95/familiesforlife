<?php

namespace App\Shared\Contracts;

/**
 * Handler contract for read-side operations.
 *
 * Each Query must have exactly one handler. Handlers are thin — they
 * delegate to repositories or query builders, then return typed results.
 */
interface QueryHandlerInterface
{
    public function handle(QueryInterface $query): mixed;
}
