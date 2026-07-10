<?php

namespace Modules\Aicem\Support\Resolvers\Exceptions;

/**
 * Ghi suggestion đã accept thất bại ở tầng module chỉ định (VD Product: dữ liệu hiện tại có
 * field KHÔNG liên quan không hợp lệ) — resolver dịch ValidationException raw sang thông báo rõ
 * ràng thay vì để lộ ra UI (spec/AICEM_Technical_Specification.md mục 11.1).
 */
class AicemSuggestionApplyException extends \RuntimeException
{
}
