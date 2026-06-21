<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kode OTP</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 480px;
      margin: 0 auto;
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .header {
      background: linear-gradient(135deg, #ff9eb5, #ffd6e0);
      padding: 32px 24px;
      text-align: center;
    }
    .header h1 {
      margin: 0;
      font-size: 22px;
      color: #222;
    }
    .body {
      padding: 32px 28px;
    }
    .body p {
      color: #555;
      font-size: 15px;
      line-height: 1.6;
      margin-bottom: 16px;
    }
    .otp-box {
      background: #fff5f8;
      border: 2px dashed #ffb3c7;
      border-radius: 12px;
      text-align: center;
      padding: 20px;
      margin: 24px 0;
    }
    .otp-code {
      font-size: 42px;
      font-weight: 900;
      color: #e91e8c;
      letter-spacing: 12px;
    }
    .otp-note {
      font-size: 13px;
      color: #888;
      margin-top: 8px;
    }
    .footer {
      text-align: center;
      padding: 20px;
      font-size: 12px;
      color: #aaa;
      border-top: 1px solid #f0f0f0;
    }
  </style>
</head>
<body>
  <div class="container">

    <div class="header">
      <h1>🔐 Reset Password</h1>
    </div>

    <div class="body">

      <p>Halo, <strong>{{ $userName }}</strong>!</p>

      <p>
        Kami menerima permintaan reset password untuk akun kamu.
        Gunakan kode OTP berikut untuk melanjutkan:
      </p>

      <div class="otp-box">
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-note">Berlaku selama <strong>10 menit</strong></div>
      </div>

      <p>
        Jika kamu tidak meminta reset password, abaikan email ini.
        Akun kamu tetap aman.
      </p>

    </div>

    <div class="footer">
      &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>

  </div>
</body>
</html>