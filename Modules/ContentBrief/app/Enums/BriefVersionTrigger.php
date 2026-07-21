<?php

namespace Modules\ContentBrief\Enums;

/** spec/ContentBrief_Technical_Specification.md §3.2 — lý do 1 version được tạo ra. */
enum BriefVersionTrigger: string
{
    case Created   = 'created';    // tạo brief lần đầu
    case Edited    = 'edited';     // sửa nội dung khi đang draft (hoặc bản draft mới sau reject)
    case Submitted = 'submitted';  // gửi duyệt — KHÔNG đổi snapshot, chỉ đổi status (§3.3)
    case Approved  = 'approved';   // được duyệt
    case Rejected  = 'rejected';   // bị từ chối
    case Restored  = 'restored';   // phục hồi từ 1 version cũ hơn
}
