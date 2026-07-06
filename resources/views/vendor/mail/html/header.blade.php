@props(['url'])
<tr>
<td class="header" style="background: linear-gradient(135deg, #4338ca 0%, #4f46e5 60%, #6366f1 100%); padding: 24px 0 20px;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto;">
    <tr>
        <td style="
            width: 34px; height: 34px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            text-align: center; vertical-align: middle;
            font-size: 16px;
        ">⚡</td>
        <td style="
            color: #ffffff;
            font-size: 19px;
            font-weight: 700;
            letter-spacing: -0.3px;
            padding-left: 10px;
            vertical-align: middle;
            white-space: nowrap;
        ">{{ config('app.name') }}</td>
    </tr>
    </table>
</a>
</td>
</tr>
