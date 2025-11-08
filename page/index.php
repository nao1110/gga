<?php
// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

// 共通処理読み込み
require_once __DIR__ . '/../lib/helpers.php';

// メッセージ取得
$success = getSessionMessage('success');
$error = getSessionMessage('error');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CareerTre - キャリトレ | キャリア相談を日常の身近なものに</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body>
  <!-- メインコンテナ -->
  <main class="hero-container">
    <div class="container-narrow">
      
      <!-- メッセージ表示 -->
      <?php if ($success): ?>
        <div class="alert alert-success fade-in" style="margin-top: var(--spacing-lg);">
          <?= h($success) ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error fade-in" style="margin-top: var(--spacing-lg);">
          <?= h($error) ?>
        </div>
      <?php endif; ?>
      
      <!-- ヒーローセクション -->
      <section class="hero-section fade-in">
        <div class="hero-content">
          <p class="hero-subtitle">キャリア相談を日常の身近なものに</p>
          <h1 class="hero-title">
            <span class="logo-primary">CareerTre</span>
          </h1>
          <p class="hero-tagline">-キャリトレ-</p>
          <p class="hero-description">
            国家資格キャリアコンサルタントを起点に<br>
            専門家を「増やす」。相談を「日常」にするアプリ。
          </p>
        </div>

        <!-- イラスト -->
        <div class="hero-illustration">
          <svg width="300" height="200" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
            <!-- テーブル -->
            <rect x="50" y="120" width="200" height="60" fill="none" stroke="#2C3E50" stroke-width="3"/>
            
            <!-- 人物1 -->
            <circle cx="100" cy="80" r="20" fill="none" stroke="#2C3E50" stroke-width="2"/>
            <path d="M 80 100 Q 100 90 120 100" fill="none" stroke="#2C3E50" stroke-width="2"/>
            <path d="M 90 115 L 90 95 M 90 105 L 75 105 M 90 105 L 105 105" stroke="#2C3E50" stroke-width="2" fill="none"/>
            
            <!-- 人物2 -->
            <circle cx="200" cy="80" r="20" fill="none" stroke="#2C3E50" stroke-width="2"/>
            <path d="M 180 100 Q 200 90 220 100" fill="none" stroke="#2C3E50" stroke-width="2"/>
            <path d="M 210 115 L 210 95 M 210 105 L 195 105 M 210 105 L 225 105" stroke="#2C3E50" stroke-width="2" fill="none"/>
            
            <!-- ノートPC -->
            <rect x="170" y="110" width="40" height="25" fill="none" stroke="#2C3E50" stroke-width="2"/>
            <line x1="170" y1="135" x2="160" y2="140" stroke="#2C3E50" stroke-width="2"/>
            <line x1="210" y1="135" x2="220" y2="140" stroke="#2C3E50" stroke-width="2"/>
            <line x1="160" y1="140" x2="220" y2="140" stroke="#2C3E50" stroke-width="2"/>
          </svg>
        </div>
      </section>

      <!-- ログインカードセクション -->
      <section class="login-section">
        <div class="grid grid-3">
          
          <!-- キャリアコンサルタント有資格者 -->
          <article class="card login-card hover-lift">
            <div class="card-icon">
              <i data-lucide="user-check"></i>
            </div>
            <h3>キャリアコンサルタント<br>有資格者</h3>
            <div style="display: flex; gap: var(--spacing-sm); width: 100%;">
              <a href="trainer/register.php" class="btn-secondary" style="flex: 1;">
                新規登録
              </a>
              <a href="trainer/login.php" class="btn-primary" style="flex: 1;">
                ログイン
              </a>
            </div>
            
            <!-- Google認証ボタン -->
            <div style="margin-top: var(--spacing-sm); width: 100%; text-align: center;">
              <a href="../controller/trainer/google_login.php" 
                 style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; 
                        padding: 10px 16px; background-color: #fff; color: #3c4043; 
                        border: 1px solid #dadce0; border-radius: 4px; text-decoration: none; 
                        font-size: 14px; font-weight: 500; transition: all 0.2s ease;
                        box-shadow: 0 1px 2px rgba(0,0,0,0.1);"
                 onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.15)';"
                 onmouseout="this.style.backgroundColor='#fff'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.1)';">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                  <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                  <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                  <path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957C.347 6.175 0 7.55 0 9s.348 2.825.957 4.039l3.007-2.332z"/>
                  <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.163 6.656 3.58 9 3.58z"/>
                </svg>
                Googleでログイン
              </a>
            </div>
          </article>

          <!-- キャリアコンサルタント受験者 -->
          <article class="card login-card hover-lift">
            <div class="card-icon">
              <i data-lucide="book-open"></i>
            </div>
            <h3>キャリアコンサルタント<br>受験者</h3>
            <div style="display: flex; gap: var(--spacing-sm); width: 100%;">
              <a href="user/register.php" class="btn-secondary" style="flex: 1;">
                新規登録
              </a>
              <a href="user/login.php" class="btn-primary" style="flex: 1;">
                ログイン
              </a>
            </div>
            
            <!-- Google認証ボタン -->
            <div style="margin-top: var(--spacing-sm); width: 100%; text-align: center;">
              <a href="../controller/user/google_login.php" 
                 style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; 
                        padding: 10px 16px; background-color: #fff; color: #3c4043; 
                        border: 1px solid #dadce0; border-radius: 4px; text-decoration: none; 
                        font-size: 14px; font-weight: 500; transition: all 0.2s ease;
                        box-shadow: 0 1px 2px rgba(0,0,0,0.1);"
                 onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.15)';"
                 onmouseout="this.style.backgroundColor='#fff'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.1)';">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                  <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                  <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                  <path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957C.347 6.175 0 7.55 0 9s.348 2.825.957 4.039l3.007-2.332z"/>
                  <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.163 6.656 3.58 9 3.58z"/>
                </svg>
                Googleでログイン
              </a>
            </div>
          </article>

          <!-- キャリア相談者 -->
          <article class="card login-card hover-lift">
            <div class="card-icon">
              <i data-lucide="message-circle"></i>
            </div>
            <h3>キャリア<br>相談者</h3>
            <div style="display: flex; gap: var(--spacing-sm); width: 100%;">
              <a href="client/register.php" class="btn-secondary" style="flex: 1;">
                新規登録
              </a>
              <a href="client/login.php" class="btn-primary" style="flex: 1;">
                ログイン
              </a>
            </div>
          </article>

        </div>
      </section>

      <!-- フッター -->
      <footer class="footer">
        <p>&copy; 2025 CareerTre - キャリトレ All rights reserved.</p>
      </footer>

    </div>
  </main>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>
</body>
</html>
