<?php

namespace App\Shared\Contracts;

/**
 * Marker interface for all write-side operations (CQRS Command).
 *
 * A Command encapsulates the intent to change state.
 * Commands should be explicit about what they do (e.g., CreateProduct, AssignTask).
 * Implement this in your Command DTO, then create a corresponding CommandHandler.
 *
 * Convention:
 *   - Command class name: CreateProduct
 *   - Handler class name: CreateProductHandler
 *   - Return type: void or an ID/result value
 */
interface CommandInterface {}
