<?php
// 共通処理読み込み
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/helpers.php';
session_start();

// エラーメッセージ・成功メッセージ取得
$error = getSessionMessage('error');
$errors = getSessionMessage('errors');
$success = getSessionMessage('success');
$old_name = getSessionMessage('old_name');
$old_email = getSessionMessage('old_email');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>新規登録 - CareerTre キャリトレ</title>
  
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

      <!-- 新規登録フォーム -->
      <section class="form-section fade-in">
        <article class="form-card">
          <div class="form-header">
            <div class="card-icon">
              <i data-lucide="graduation-cap"></i>
            </div>
            <h2>新規登録</h2>
            <p class="form-subtitle">国家資格キャリアコンサルタント</p>
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

          <form action="../../controller/trainer/register_process.php" method="POST" class="register-form">
            
            <!-- 名前 -->
            <div class="form-group">
              <label for="name">
                お名前 <span class="required">*</span>
              </label>
              <input 
                type="text" 
                id="name" 
                name="name" 
                placeholder="山田 花子" 
                value="<?= h($old_name ?? '') ?>"
                required
                autocomplete="name"
              >
            </div>

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
              <small class="form-help">ログイン時に使用します</small>
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
                placeholder="8文字以上" 
                required
                minlength="8"
                autocomplete="new-password"
              >
              <small class="form-help">8文字以上で設定してください</small>
            </div>

            <!-- パスワード確認 -->
            <div class="form-group">
              <label for="password_confirm">
                パスワード（確認） <span class="required">*</span>
              </label>
              <input 
                type="password" 
                id="password_confirm" 
                name="password_confirm" 
                placeholder="パスワードを再入力" 
                required
                minlength="8"
                autocomplete="new-password"
              >
            </div>

            <!-- 送信ボタン -->
            <button type="submit" class="btn-primary btn-large">
              新規登録
            </button>

          </form>

          <!-- ログインリンク -->
          <div class="form-footer">
            <p>すでにアカウントをお持ちの方</p>
            <a href="login.php" class="btn-secondary">
              ログイン
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

  <!-- パスワード確認のバリデーション -->
  <script>
    document.querySelector('.register-form').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const passwordConfirm = document.getElementById('password_confirm').value;
      
      if (password !== passwordConfirm) {
        e.preventDefault();
        alert('パスワードが一致しません。もう一度確認してください。');
        document.getElementById('password_confirm').focus();
      }
    });
  </script>
</body>
</html>
