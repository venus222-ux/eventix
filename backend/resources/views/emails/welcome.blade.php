<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Aboard</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

    <div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">

        <div style="background: #4f46e5; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); padding: 48px 30px; text-align: center; color: white;">
            <div style="background: rgba(255, 255, 255, 0.2); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto 20px; line-height: 60px; font-size: 30px;">
                🚀
            </div>
            <h1 style="margin: 0; font-size: 28px; font-weight: 800; letter-spacing: -0.5px;">Welcome, {{ $user->name }}!</h1>
            <p style="margin: 10px 0 0; font-size: 16px; opacity: 0.9; font-weight: 400;">
                We're thrilled to have you in the community.
            </p>
        </div>

        <div style="padding: 40px 35px; color: #1e293b;">
            <p style="font-size: 16px; line-height: 1.6; margin-top: 0;">
                Hi <strong>{{ $user->name }}</strong>,
            </p>

            <p style="font-size: 16px; line-height: 1.6; color: #475569;">
                Your account is officially ready to go. You’ve joined thousands of others using our platform to streamline their workflow and hit their goals faster.
            </p>

            <div style="margin: 32px 0; padding: 20px; background-color: #f1f5f9; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <p style="margin: 0; font-size: 14px; color: #334155; display: flex; align-items: center;">
                    <span style="font-size: 18px; margin-right: 10px;">💡</span>
                    <strong>Pro Tip:</strong> Complete your profile details to unlock personalized recommendations.
                </p>
            </div>

            <div style="text-align: center; margin: 35px 0;">
                <a href="https://your-app.com/dashboard"
                   style="background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 10px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);">
                    Get Started Now
                </a>
            </div>

            <p style="font-size: 14px; color: #94a3b8; line-height: 1.5; text-align: center; margin-top: 40px;">
                Need help? <a href="mailto:support@yourcompany.com" style="color: #4f46e5; text-decoration: none; font-weight: 600;">Contact Support</a>
            </p>
        </div>

        <div style="background-color: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #f1f5f9;">
            <div style="margin-bottom: 15px;">
                <span style="color: #cbd5e1; margin: 0 8px; font-size: 18px;">•</span>
            </div>
            <p style="margin: 0; font-size: 12px; color: #94a3b8; letter-spacing: 0.5px; text-transform: uppercase;">
                © {{ date('Y') }} Your Company Inc.
            </p>
            <p style="margin: 8px 0 0; font-size: 11px; color: #cbd5e1;">
                123 Tech Avenue, San Francisco, CA 94107
            </p>
        </div>

    </div>

</body>
</html>
