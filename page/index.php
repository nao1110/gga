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
          </article>

          <!-- キャリア相談者 -->
          <article class="card login-card hover-lift">
            <div class="card-icon">
              <i data-lucide="message-circle"></i>
            </div>
            <h3>キャリア<br>相談者</h3>
            <a href="login_client.php" class="btn-primary">
              LOGIN
            </a>
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
