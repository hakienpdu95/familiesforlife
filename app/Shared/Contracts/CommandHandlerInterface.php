<?php

namespace App\Shared\Contracts;

/**
 * Handler contract for write-side operations.
 *
 * Each Command must have exactly one handler. Handlers own the business logic
 * for the use case — validate invariants, mutate state, dispatch events.
 */
interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
