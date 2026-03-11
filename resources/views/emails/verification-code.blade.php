<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .logo {
            font-size: 24px;
            font-weight: 600;
            color: #7BA3C9;
            margin-bottom: 24px;
        }
        h2 {
            color: #333;
            margin: 0 0 16px 0;
            font-size: 22px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }
        .code-box {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #333;
            font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
        }
        .expire {
            color: #E8B86D;
            font-weight: 500;
        }
        .footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 13px;
        }
        .footer p {
            color: #999;
            margin: 0 0 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">Ledger of Decisions</div>

            @if($type === 'register')
                <h2>歡迎加入</h2>
                <p>感謝您註冊 Ledger of Decisions！請使用以下驗證碼完成註冊：</p>
            @else
                <h2>密碼重設請求</h2>
                <p>我們收到了您的密碼重設請求。請使用以下驗證碼重設您的密碼：</p>
            @endif

            <div class="code-box">
                <div class="code">{{ $code }}</div>
            </div>

            <p>此驗證碼將在 <span class="expire">10 分鐘</span>後過期。</p>

            <div class="footer">
                <p>如果您沒有請求此驗證碼，請忽略此郵件。</p>
                <p>- Ledger of Decisions 團隊</p>
            </div>
        </div>
    </div>
</body>
</html>
