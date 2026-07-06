<?php

namespace Modules\Survey\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldImmutableException extends Exception
{
    public function __construct(string $reason = 'Field không thể thay đổi sau khi survey đã có responses.')
    {
        parent::__construct($reason, 422);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
