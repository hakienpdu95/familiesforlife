<?php

namespace Modules\AccessTrade\Exceptions;

use RuntimeException;

/**
 * Ném bởi AccessTradeClient khi request tới AccessTrade Publisher API thất bại (HTTP lỗi, thiếu
 * access_token, hoặc response không đúng shape mong đợi) — nơi gọi (SyncOffersAction/
 * SyncTopProductsAction) bắt riêng exception này để log + bỏ qua merchant đang xử lý, không chặn
 * các merchant còn lại trong cùng 1 lần đồng bộ.
 */
class AccessTradeApiException extends RuntimeException
{
}
