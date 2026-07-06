<?php

namespace App\Shared\Contracts;

/**
 * Marker interface for all write-side operations (CQRS Command).
 *
 * A Command encapsulates the intent to change state.
 * Commands should be explicit about what they do (e.g., CreateLead, AssignTask).
 * Implement this in your Command DTO, then create a corresponding CommandHandler.
 *
 * Convention:
 *   - Command class name: CreateLead
 *   - Handler class name: CreateLeadHandler
 *   - Return type: void or an ID/result value
 */
interface CommandInterface {}
