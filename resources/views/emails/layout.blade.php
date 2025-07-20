<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $subject ?? 'ایمیل شما' }}</title>
    <style>
        /* Reset و تنظیمات پایه */
        body {
            font-family: "Tahoma", "Vazir", sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        a {
            color: #1a73e8;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .email-container {
            max-width: 600px;
            margin: auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-weight: 700;
            color: #222;
            font-size: 24px;
        }
        .content {
            line-height: 1.6;
            font-size: 16px;
        }
        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 15px;
            text-align: center;
        }
        @media screen and (max-width: 480px) {
            body, .email-container {
                padding: 15px;
                text-align: right;
                direction: rtl;
            }
            .email-container {
                width: 100% !important;
                border-radius: 0;
                box-shadow: none;
                text-align: right;
            }
            .header h1 {
                font-size: 20px;
            }
            .content {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>{{ $subject ?? 'پیام جدید' }}</h1>
    </div>
    <div class="content">
        {!! $content !!}
    </div>
    <div class="footer">
        این ایمیل به صورت خودکار ارسال شده است. لطفا به آن پاسخ ندهید.
      <br>
        <a href="https://arta20.top" target="_blank" style="color: #1a73e8;">همراه سیمرغ ایران</a>

    </div>
</div>
</body>
</html>
