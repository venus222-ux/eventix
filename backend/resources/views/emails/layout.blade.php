<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Eventix'))</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#111827;">
    <span style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader')</span>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                       style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#4f46e5;padding:24px 32px;color:#ffffff;font-size:20px;font-weight:700;letter-spacing:.3px;">
                            {{ config('app.name', 'Eventix') }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;font-size:15px;line-height:1.6;color:#111827;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
                            Need help? Reply to this email or write to
                            <a href="mailto:{{ config('mail.from.address') }}" style="color:#4f46e5;text-decoration:none;">{{ config('mail.from.address') }}</a>.<br>
                            You are receiving this email because of an order placed on {{ config('app.name', 'Eventix') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>