<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="vi">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>{{ config('app.name') }}</title>
<style>
body { margin:0; padding:0; background:#f1f5f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#374151; }
table { border-collapse:collapse; }
@media only screen and (max-width:620px) {
  .email-wrapper { width:100% !important; }
  .email-body { padding:28px 20px !important; }
}
</style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f1f5f9; padding:32px 16px;">
<tr><td align="center">

  {{-- Card --}}
  <table class="email-wrapper" width="570" cellpadding="0" cellspacing="0" role="presentation"
         style="background:#ffffff; border-radius:8px; border:1px solid #e0e7ff; box-shadow:0 2px 8px rgba(79,70,229,0.06);">

    {{-- Header --}}
    <tr>
      <td style="background:linear-gradient(135deg,#4338ca 0%,#4f46e5 60%,#6366f1 100%); border-radius:8px 8px 0 0; padding:20px; text-align:center;">
        <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto;">
        <tr>
          <td style="width:32px;height:32px;background:rgba(255,255,255,0.2);border-radius:6px;text-align:center;vertical-align:middle;font-size:16px;">⚡</td>
          <td style="color:#fff;font-size:18px;font-weight:700;padding-left:10px;vertical-align:middle;letter-spacing:-0.3px;">{{ config('app.name') }}</td>
        </tr>
        </table>
      </td>
    </tr>

    {{-- Body --}}
    <tr>
      <td class="email-body" style="padding:36px 40px 28px;">

        @if(!empty($extra['body']))
          <div style="font-size:15px;line-height:1.6;color:#374151;">{!! nl2br(e($extra['body'])) !!}</div>
        @else
          <p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Xin chào,</p>
          <p style="font-size:15px;line-height:1.6;margin:0 0 16px;color:#6b7280;">Bạn nhận được thông báo tự động này từ hệ thống workflow.</p>

          @if($subjectType && $subjectId)
          <div style="background:#eef2ff;border-left:4px solid #4f46e5;border-radius:0 6px 6px 0;padding:14px 18px;margin:16px 0;">
            <p style="margin:0;font-size:14px;color:#312e81;">
              Đối tượng: <strong>{{ $subjectType }} #{{ $subjectId }}</strong>
            </p>
          </div>
          @endif

          @if($actorEmail)
          <p style="font-size:14px;color:#6b7280;margin:8px 0;">
            Người thực hiện: <strong style="color:#374151;">{{ $actorName ?? $actorEmail }}</strong>
          </p>
          @endif

          @if(!empty($extra))
          <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;border:1px solid #e0e7ff;border-radius:6px;overflow:hidden;">
            @foreach($extra as $k => $v)
            @if(!is_array($v))
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:9px 14px;font-size:13px;color:#6b7280;background:#f8faff;width:38%;font-weight:500;">{{ $k }}</td>
              <td style="padding:9px 14px;font-size:13px;color:#1e1b4b;">{{ $v }}</td>
            </tr>
            @endif
            @endforeach
          </table>
          @endif
        @endif

      </td>
    </tr>

    {{-- Footer --}}
    <tr>
      <td style="border-top:1px solid #e0e7ff;padding:16px 40px 24px;text-align:center;">
        <p style="font-size:12px;color:#94a3b8;margin:0;line-height:1.6;">
          © {{ date('Y') }} <strong>{{ config('app.name') }}</strong> —
          Email được gửi tự động, vui lòng không trả lời trực tiếp.
        </p>
      </td>
    </tr>

  </table>

</td></tr>
</table>
</body>
</html>
