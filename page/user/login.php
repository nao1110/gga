<?php
// 共通処理読み込み
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/helpers.php';
session_start();

// エラーメッセージ・成功メッセージ取得
$error = getSessionMessage('error');
$errors = getSessionMessage('errors');
$success = getSessionMessage('success');
$old_email = getSessionMessage('old_email');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン - CareerTre キャリトレ</title>
  
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
          トップに戻る
        </a>
        <h1 class="logo-primary">CareerTre</h1>
        <p class="hero-tagline">-キャリトレ-</p>
      </header>

      <!-- ログインフォーム -->
      <section class="form-section fade-in">
        <article class="form-card">
          <div class="form-header">
            <div class="card-icon">
              <i data-lucide="log-in"></i>
            </div>
            <h2>ログイン</h2>
            <p class="form-subtitle">キャリアコンサルタント受験者</p>
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

          <?php if ($errors): ?>
            <div class="alert alert-error">
              <ul style="margin: 0; padding-left: 1.25rem;">
                <?php foreach ($errors as $err): ?>
                  <li><?= h($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form action="../../controller/user/login_process.php" method="POST" class="register-form">
            
            <!-- メールアドレス -->
            <div class="form-group">
              <label for="email">
                メールアドレス <span class="required">*</span>
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

            <!-- パスワード -->
            <div class="form-group">
              <label for="password">
                パスワード <span class="required">*</span>
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

            <!-- パスワードを忘れた場合 -->
            <div class="form-group">
              <a href="reset_password.php" class="link-primary" style="font-size: var(--font-size-sm);">
                パスワードをお忘れの方
              </a>
            </div>

            <!-- ログインボタン -->
            <button type="submit" class="btn-primary btn-large">
              ログイン
            </button>

          </form>

          <!-- 新規登録リンク -->
          <div class="form-footer">
            <p>アカウントをお持ちでない方</p>
            <a href="register.php" class="btn-secondary">
              新規登録
            </a>
          </div>

        </article>
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
