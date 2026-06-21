<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selamat Jadi Seller</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      padding: 24px 16px;
    }

    .container {
      max-width: 500px;
      margin: 0 auto;
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }

    /* HERO */
    .hero {
      background: linear-gradient(135deg, #ff9eb5 0%, #ffccd8 100%);
      padding: 40px 28px 36px;
      text-align: center;
    }

    .hero-emoji {
      font-size: 56px;
      display: block;
      margin-bottom: 14px;
    }

    .hero h1 {
      font-size: 22px;
      font-weight: 800;
      color: #222;
      margin-bottom: 6px;
    }

    .hero p {
      font-size: 14px;
      color: #555;
      line-height: 1.5;
    }

    /* BODY */
    .body {
      padding: 28px;
    }

    .greeting {
      font-size: 15px;
      color: #333;
      line-height: 1.6;
      margin-bottom: 24px;
    }

    .greeting strong {
      color: #e91e8c;
    }

    /* INFO BOX */
    .info-box {
      background: #fff8fa;
      border-left: 4px solid #f57f9a;
      border-radius: 10px;
      padding: 16px 18px;
      margin-bottom: 24px;
    }

    .info-box h3 {
      font-size: 14px;
      font-weight: 700;
      color: #222;
      margin-bottom: 10px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      margin-bottom: 6px;
    }

    .info-row span:first-child {
      color: #888;
    }

    .info-row span:last-child {
      font-weight: 600;
      color: #222;
    }

    /* STEPS */
    .steps-title {
      font-size: 15px;
      font-weight: 700;
      color: #222;
      margin-bottom: 14px;
    }

    .step-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 12px;
    }

    .step-num {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: #f57f9a;
      color: white;
      font-size: 13px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .step-text {
      font-size: 13px;
      color: #555;
      line-height: 1.5;
      padding-top: 4px;
    }

    /* CTA */
    .cta {
      text-align: center;
      margin-top: 28px;
    }

    .cta a {
      display: inline-block;
      padding: 14px 32px;
      background: linear-gradient(90deg, #e91e8c, #ff6bb3);
      color: #fff;
      text-decoration: none;
      border-radius: 28px;
      font-size: 15px;
      font-weight: 700;
      box-shadow: 0 4px 14px rgba(233,30,140,0.3);
    }

    /* FOOTER */
    .footer {
      background: #f9f9f9;
      padding: 18px 28px;
      text-align: center;
      font-size: 12px;
      color: #aaa;
      border-top: 1px solid #f0f0f0;
    }

    .footer a {
      color: #e91e8c;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">

    <!-- HERO -->
    <div class="hero">
      <span class="hero-emoji">🎉</span>
      <h1>Selamat, Kamu Resmi Jadi Seller!</h1>
      <p>Akun seller kamu telah aktif dan siap digunakan</p>
    </div>

    <!-- BODY -->
    <div class="body">

      <p class="greeting">
        Halo, <strong>{{ $userName }}</strong>!<br><br>
        Terima kasih telah bergabung sebagai seller di <strong>{{ config('app.name') }}</strong>.
        Toko kamu sudah terdaftar dan siap untuk mulai berjualan!
      </p>

      <!-- INFO TOKO -->
      <div class="info-box">
        <h3>📋 Informasi Toko</h3>
        <div class="info-row">
          <span>Nama Toko</span>
          <span>{{ $storeName }}</span>
        </div>
        <div class="info-row">
          <span>Email</span>
          <span>{{ $userEmail }}</span>
        </div>
        <div class="info-row">
          <span>Status</span>
          <span style="color: #2ecc71;">✅ Aktif</span>
        </div>
      </div>

      <!-- LANGKAH SELANJUTNYA -->
      <p class="steps-title">🚀 Langkah Selanjutnya</p>

      <div class="step-item">
        <div class="step-num">1</div>
        <div class="step-text">
          Login ke <strong>dashboard seller</strong> untuk mulai mengelola toko kamu
        </div>
      </div>

      <div class="step-item">
        <div class="step-num">2</div>
        <div class="step-text">
          Tambahkan <strong>produk pertama</strong> kamu agar pembeli bisa menemukannya
        </div>
      </div>

      <div class="step-item">
        <div class="step-num">3</div>
        <div class="step-text">
          Lengkapi <strong>profil toko</strong> dengan foto dan deskripsi yang menarik
        </div>
      </div>

      <!-- CTA BUTTON -->
      <div class="cta">
        <a href="http://layananapp.my.id" target="_blank">
          Buka Dashboard Seller
        </a>
      </div>

    </div>

    <!-- FOOTER -->
    <div class="footer">
      &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
      Email ini dikirim ke <a href="mailto:{{ $userEmail }}">{{ $userEmail }}</a>
    </div>

  </div>
</body>
</html>