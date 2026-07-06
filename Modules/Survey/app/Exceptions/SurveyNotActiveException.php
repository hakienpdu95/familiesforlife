<?php

namespace Modules\Survey\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Survey\Enums\SurveyStatus;

class SurveyNotActiveException extends Exception
{
    public function __construct(SurveyStatus $status)
    {
        $message = match ($status) {
            SurveyStatus::Draft  => 'Survey chưa active.',
            SurveyStatus::Closed => 'Survey đã đóng.',
            default              => 'Survey không ở trạng thái có thể nhận phản hồi.',
        };

        parent::__construct($message, 403);
    }

    public function report(): bool
    {
        return false;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 403);
    }
}
