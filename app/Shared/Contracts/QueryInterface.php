<?php

namespace App\Shared\Contracts;

/**
 * Marker interface for all read-side operations (CQRS Query).
 *
 * A Query encapsulates the intent to read data without side effects.
 * Implement this in your Query DTO, then create a corresponding QueryHandler.
 *
 * Convention:
 *   - Query class name:   GetLeadsByStatus
 *   - Handler class name: GetLeadsByStatusHandler
 *   - Return type declared on handler's handle() method
 */
interface QueryInterface {}
