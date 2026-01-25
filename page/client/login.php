<?php
// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

// 共通処理読み込み
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';

// エラーメッセージ・成功メッセージ取得
$error = getSessionMessage('error');
$success = getSessionMessage('success');
$old_email = getSessionMessage('old_email');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>キャリア相談者 ログイン - CareerTre キャリアトレーナーズ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../assets/css/variables.css">
  <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body>
  <!-- メインコンテナ -->
  <main class="hero-container">
    <div class="container-narrow">
      
      <!-- ヘッダー -->
      <header class="page-header fade-in">
        <a href="../index.php" class="back-link">
          <i data-lucide="arrow-left"></i>
          トップページに戻る
        </a>
        <h1 class="logo-primary">CareerTre</h1>
        <p class="hero-tagline">-キャリアトレーナーズ-</p>
      </header>

      <!-- ログインフォーム -->
      <section class="form-section fade-in">
        <article class="form-card">
          <div class="form-header">
            <div class="form-icon">
              <i data-lucide="message-circle"></i>
            </div>
            <h2>キャリア相談者 ログイン</h2>
            <p class="form-description">キャリアの悩みや相談をお待ちしています</p>
          </div>

          <?php if ($success): ?>
            <div class="alert alert-success">
              <?= h($success) ?>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="alert alert-error">
              <?= h($error) ?>
            </div>
          <?php endif; ?>

          <form action="../../controller/client/login_process.php" method="POST" class="auth-form">
            <div class="form-group">
              <label for="email">
                メールアドレス
                <span class="required">*</span>
              </label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                placeholder="example@email.com" 
                value="<?= h($old_email ?? '') ?>"
                required
                autocomplete="email"
              >
            </div>

            <div class="form-group">
              <label for="password">
                パスワード
                <span class="required">*</span>
              </label>
              <input 
                type="password" 
                id="password" 
                name="password" 
                placeholder="パスワードを入力" 
                required
                autocomplete="current-password"
              >
            </div>

            <button type="submit" class="btn-primary btn-large btn-block">
              <i data-lucide="log-in"></i>
              ログイン
            </button>
          </form>

          <div class="form-footer">
            <p>アカウントをお持ちでない方は</p>
            <a href="register.php" class="btn-link">
              新規登録はこちら
              <i data-lucide="arrow-right"></i>
            </a>
          </div>
        </article>
      </section>

      <!-- フッター -->
      <footer class="footer">
        <p>&copy; 2025 CareerTre - キャリアトレーナーズ All rights reserved.</p>
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
