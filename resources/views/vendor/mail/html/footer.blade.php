<tr>
<td style="padding: 8px 0 32px;">
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center" style="padding: 20px 32px 0;">
    <p style="color: #94a3b8; font-size: 12px; margin: 0 0 6px; line-height: 1.6;">
        {{ Illuminate\Mail\Markdown::parse($slot) }}
    </p>
    <p style="color: #cbd5e1; font-size: 11px; margin: 0; line-height: 1.6;">
        © {{ date('Y') }} <strong style="color: #94a3b8;">{{ config('app.name') }}</strong>.
        Email này được gửi tự động — vui lòng không trả lời trực tiếp.
    </p>
</td>
</tr>
</table>
</td>
</tr>
