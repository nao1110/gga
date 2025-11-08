<?php
/**
 * クライアント（キャリア相談者） 予約作成ページ
 * 
 * @package CareerTre
 */

// 認証チェック
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/database.php';
requireLogin('client');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$client_id = $current_user['id'];
$client_name = $current_user['name'];

// トレーナーIDを取得
$trainer_id = isset($_GET['trainer_id']) ? intval($_GET['trainer_id']) : 0;

if (!$trainer_id) {
    redirect('/gs_code/gga/page/client/mypage.php');
    exit;
}

// エラーメッセージ取得
$errors = getSessionMessage('errors');

// データベース接続
$db = getDBConnection();

// トレーナー情報を取得
$stmt = $db->prepare("
    SELECT 
        id,
        name,
        nickname,
        email,
        career_description,
        available_time
    FROM trainers
    WHERE id = ? AND is_active = 1
");
$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    redirect('/gs_code/gga/page/client/mypage.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>予約作成 - CareerTre キャリトレ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../assets/css/variables.css">
  <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body>
  <!-- ナビゲーションヘッダー -->
  <nav class="navbar">
    <div class="container">
      <div class="navbar-content">
        <div class="navbar-brand">
          <h1 class="logo-primary" style="margin: 0; font-size: var(--font-size-xl);">CareerTre</h1>
          <span class="navbar-tagline">-キャリトレ-</span>
        </div>
        <div class="navbar-menu">
          <a href="mypage.php" class="nav-link">
            <i data-lucide="home"></i>
            <span>マイページ</span>
          </a>
          <a href="../../controller/logout.php" class="nav-link">
            <i data-lucide="log-out"></i>
            <span>ログアウト</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- メインコンテナ -->
  <main class="hero-container">
    <div class="container-narrow">
      
      <!-- ヘッダー -->
      <header class="page-header fade-in">
        <a href="mypage.php" class="back-link">
          <i data-lucide="arrow-left"></i>
          マイページに戻る
        </a>
        <h1 class="logo-primary">CareerTre</h1>
        <p class="hero-tagline">-キャリトレ-</p>
      </header>

      <!-- トレーナー情報 -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="user"></i>
              担当キャリアコンサルタント
            </h2>
          </div>
          <div class="card-body">
            <div style="padding: var(--spacing-md); background: var(--color-bg-secondary); border-radius: var(--radius-md);">
              <h3 style="margin: 0 0 var(--spacing-xs) 0;">
                <?php echo h($trainer['nickname'] ?: $trainer['name']); ?>
              </h3>
              <?php if ($trainer['nickname']): ?>
                <p style="margin: 0 0 var(--spacing-md) 0; color: var(--text-secondary); font-size: var(--font-size-sm);">
                  （<?php echo h($trainer['name']); ?>）
                </p>
              <?php endif; ?>
              
              <?php if ($trainer['career_description']): ?>
                <div style="margin-top: var(--spacing-md); padding-top: var(--spacing-md); border-top: 1px solid var(--color-border);">
                  <h4 style="margin: 0 0 var(--spacing-sm) 0; display: flex; align-items: center; gap: var(--spacing-xs);">
                    <i data-lucide="briefcase"></i>
                    経歴・専門分野
                  </h4>
                  <p style="margin: 0; white-space: pre-line; line-height: 1.6;">
                    <?php echo h($trainer['career_description']); ?>
                  </p>
                </div>
              <?php endif; ?>
              
              <?php if ($trainer['available_time']): ?>
                <div style="margin-top: var(--spacing-md); padding: var(--spacing-md); background: #f0f9ff; border-radius: var(--radius-md); border-left: 4px solid #3b82f6;">
                  <h4 style="margin: 0 0 var(--spacing-sm) 0; display: flex; align-items: center; gap: var(--spacing-xs);">
                    <i data-lucide="clock"></i>
                    対応可能時間
                  </h4>
                  <p style="margin: 0; white-space: pre-line; line-height: 1.6;">
                    <?php echo h($trainer['available_time']); ?>
                  </p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </article>
      </section>

      <!-- 予約フォーム -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="calendar-plus"></i>
              予約日時を選択
            </h2>
          </div>
          <div class="card-body">
            
            <?php if ($errors): ?>
              <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 1.25rem;">
                  <?php foreach ($errors as $err): ?>
                    <li><?php echo h($err); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form action="reserve_process.php" method="POST" class="reservation-form">
              <input type="hidden" name="trainer_id" value="<?php echo h($trainer_id); ?>">
              
              <div class="form-group">
                <label for="meeting_date">
                  希望日
                  <span class="required">*</span>
                </label>
                <input 
                  type="date" 
                  id="meeting_date" 
                  name="meeting_date" 
                  required
                  min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                >
                <small class="form-text">
                  <i data-lucide="info"></i>
                  明日以降の日付を選択してください
                </small>
              </div>

              <div class="form-group">
                <label for="meeting_time">
                  希望時間
                  <span class="required">*</span>
                </label>
                <select id="meeting_time" name="meeting_time" required>
                  <option value="">時間を選択してください</option>
                  <option value="09:00">09:00 - 10:00</option>
                  <option value="10:00">10:00 - 11:00</option>
                  <option value="11:00">11:00 - 12:00</option>
                  <option value="13:00">13:00 - 14:00</option>
                  <option value="14:00">14:00 - 15:00</option>
                  <option value="15:00">15:00 - 16:00</option>
                  <option value="16:00">16:00 - 17:00</option>
                  <option value="17:00">17:00 - 18:00</option>
                  <option value="18:00">18:00 - 19:00</option>
                  <option value="19:00">19:00 - 20:00</option>
                  <option value="20:00">20:00 - 21:00</option>
                  <option value="21:00">21:00 - 22:00</option>
                </select>
              </div>

              <div class="form-group">
                <label for="consultation_topic">
                  相談したい内容（任意）
                </label>
                <textarea 
                  id="consultation_topic" 
                  name="consultation_topic" 
                  rows="5"
                  placeholder="例：転職を考えているが、どのように進めればよいかわからない"
                ></textarea>
                <small class="form-text">
                  <i data-lucide="info"></i>
                  事前に相談内容をお知らせいただくと、より充実した面談になります
                </small>
              </div>

              <div class="form-actions">
                <a href="mypage.php" class="btn-secondary">
                  <i data-lucide="arrow-left"></i>
                  キャンセル
                </a>
                <button type="submit" class="btn-primary">
                  <i data-lucide="check"></i>
                  予約を確定する
                </button>
              </div>
            </form>
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
