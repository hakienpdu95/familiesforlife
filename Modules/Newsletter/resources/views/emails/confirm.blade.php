<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="vi">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
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

  <table class="email-wrapper" width="570" cellpadding="0" cellspacing="0" role="presentation"
         style="background:#ffffff; border-radius:8px; border:1px solid #e0e7ff; box-shadow:0 2px 8px rgba(79,70,229,0.06);">

    <tr>
      <td style="background:linear-gradient(135deg,#4338ca 0%,#4f46e5 60%,#6366f1 100%); border-radius:8px 8px 0 0; padding:20px; text-align:center;">
        <span style="color:#fff;font-size:18px;font-weight:700;letter-spacing:-0.3px;">{{ config('app.name') }}</span>
      </td>
    </tr>

    <tr>
      <td class="email-body" style="padding:36px 40px 28px;">
        <p style="font-size:15px;line-height:1.6;margin:0 0 12px;">Xin chào {{ $fullName }},</p>
        <p style="font-size:15px;line-height:1.6;margin:0 0 20px;color:#374151;">
            Bạn (hoặc ai đó dùng email này) vừa đăng ký nhận bản tin từ {{ config('app.name') }}. Bấm nút bên
            dưới để xác nhận — nếu không xác nhận, bạn sẽ không nhận được bất kỳ email nào khác.
        </p>
        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto;">
          <tr>
            <td style="border-radius:6px;background:#4f46e5;">
              <a href="{{ $confirmUrl }}" target="_blank"
                 style="display:inline-block;padding:12px 28px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                Xác nhận đăng ký
              </a>
            </td>
          </tr>
        </table>
        <p style="font-size:13px;line-height:1.6;margin:20px 0 0;color:#94a3b8;">
            Nếu nút không hoạt động, dán liên kết sau vào trình duyệt: <br>
            <span style="word-break:break-all;">{{ $confirmUrl }}</span>
        </p>
      </td>
    </tr>

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
