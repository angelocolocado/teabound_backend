<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <style>
        body { margin: 0; padding: 0; background-color: #617255; color: #25251e; font-family: Arial, Helvetica, sans-serif; }
        table { border-collapse: collapse; }
        .shell { width: 100%; background-color: #617255; }
        .card { width: 100%; max-width: 560px; background-color: #fff9e5; border-radius: 20px; }
        .content { padding: 42px 48px 38px; }
        .brand { color: #8c431d; font-family: Georgia, "Times New Roman", serif; font-size: 27px; font-weight: bold; letter-spacing: 0.3px; }
        .brand-mark { width: 42px; height: 42px; margin: 0 auto 10px; }
        h1 { margin: 0 0 18px; color: #25251e; font-family: Georgia, "Times New Roman", serif; font-size: 26px; line-height: 1.2; text-align: center; }
        p { margin: 0 0 16px; color: #45483c; font-size: 15px; line-height: 1.65; }
        .intro { text-align: center; }
        .button-cell { padding: 10px 0 24px; text-align: center; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #8c431d; border-radius: 999px; color: #ffffff !important; font-size: 15px; font-weight: bold; line-height: 1; text-decoration: none; }
        .notice { margin: 0; padding: 14px 16px; border-left: 3px solid #d6a86a; background-color: #f9eac1; color: #68543d; font-size: 13px; line-height: 1.55; }
        .footer { padding-top: 28px; color: #7a806d; font-size: 12px; line-height: 1.5; text-align: center; }
        @media only screen and (max-width: 600px) {
            .card { border-radius: 0; }
            .content { padding: 34px 24px 30px; }
        }
    </style>
</head>
<body>
<table role="presentation" class="shell" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding: 40px 16px;">
            <table role="presentation" class="card" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="content">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center">
                                    <svg class="brand-mark" viewBox="0 0 48 48" role="img" aria-label="Tea Bound">
                                        <path d="M24 43V7M24 24C15 23 9 17 9 8c9 0 15 6 15 16ZM24 31c9-1 15-7 15-16-9 0-15 6-15 16ZM24 18c-5-1-8-5-8-11 5 0 8 4 8 11Z" fill="none" stroke="#8c431d" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4"/>
                                    </svg>
                                    <div class="brand">Tea Bound</div>
                                </td>
                            </tr>
                        </table>

                        <h1>Password reset request</h1>
                        <p class="intro">Hello {{ $user->name ?? 'User' }},</p>
                        <p class="intro">We received a request to reset your password. Click the button below to set a new password.</p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="button-cell">
                                    <a href="{{ $url }}" class="btn">Reset Password</a>
                                </td>
                            </tr>
                        </table>

                        <p class="notice">If you did not request this, please ignore this email. This link will expire within 60 minutes.</p>

                        <div class="footer">
                            &copy; {{ date('Y') }} Tea Bound. All rights reserved.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
