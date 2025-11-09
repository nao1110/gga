<?php
/**
 * クライアント（キャリア相談者） マイページ
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

// 成功メッセージ取得
$success = getSessionMessage('success');

// 曜日を取得する関数
function getJapaneseWeekday($date) {
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $weekdays[date('w', strtotime($date))];
}

// データベース接続
$db = getDBConnection();

// トレーナー一覧を取得（プロフィール情報付き）
$stmt = $db->prepare("
    SELECT 
        id,
        name,
        nickname,
        email,
        career_description,
        available_time
    FROM trainers
    WHERE is_active = 1
    ORDER BY id ASC
");
$stmt->execute();
$trainers = $stmt->fetchAll();

// 自分の予約情報を取得
$stmt_reserves = $db->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.meet_url,
        r.consultation_topic,
        r.status,
        r.created_at,
        t.name as trainer_name,
        t.email as trainer_email
    FROM client_reserves r
    LEFT JOIN trainers t ON r.trainer_id = t.id
    WHERE r.client_id = ?
    ORDER BY r.meeting_date DESC
");
$stmt_reserves->execute([$client_id]);
$my_reservations = $stmt_reserves->fetchAll();

// ステータスごとに分類
$pending_reserves = [];
$confirmed_reserves = [];
$past_reserves = [];

foreach ($my_reservations as $reserve) {
    if ($reserve['status'] === 'pending') {
        $pending_reserves[] = $reserve;
    } elseif ($reserve['status'] === 'confirmed') {
        $confirmed_reserves[] = $reserve;
    } else {
        $past_reserves[] = $reserve;
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>マイページ - CareerTre キャリトレ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../assets/css/variables.css">
  <link rel="stylesheet" href="../../assets/css/custom.css">
  
  <style>
    .trainer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: var(--spacing-lg);
      margin-top: var(--spacing-lg);
    }
    
    .trainer-card {
      background: white;
      border-radius: var(--radius-lg);
      padding: var(--spacing-lg);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .trainer-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }
    
    .trainer-header {
      display: flex;
      align-items: center;
      gap: var(--spacing-md);
      margin-bottom: var(--spacing-md);
      padding-bottom: var(--spacing-md);
      border-bottom: 2px solid var(--color-border);
    }
    
    .trainer-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      font-weight: bold;
    }
    
    .trainer-name-section h3 {
      margin: 0 0 var(--spacing-xs) 0;
      color: var(--text-primary);
    }
    
    .trainer-real-name {
      font-size: var(--font-size-sm);
      color: var(--text-secondary);
    }
    
    .trainer-career {
      margin-bottom: var(--spacing-md);
      padding: var(--spacing-md);
      background: var(--color-bg-secondary);
      border-radius: var(--radius-md);
      border-left: 4px solid #667eea;
    }
    
    .trainer-career h4 {
      margin: 0 0 var(--spacing-sm) 0;
      font-size: var(--font-size-md);
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: var(--spacing-xs);
    }
    
    .trainer-career p {
      margin: 0;
      font-size: var(--font-size-sm);
      line-height: 1.6;
      color: var(--text-secondary);
      white-space: pre-line;
    }
    
    .trainer-availability {
      margin-bottom: var(--spacing-md);
      padding: var(--spacing-md);
      background: #f0f9ff;
      border-radius: var(--radius-md);
      border-left: 4px solid #3b82f6;
    }
    
    .trainer-availability h4 {
      margin: 0 0 var(--spacing-sm) 0;
      font-size: var(--font-size-md);
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: var(--spacing-xs);
    }
    
    .trainer-availability p {
      margin: 0;
      font-size: var(--font-size-sm);
      line-height: 1.6;
      color: var(--text-secondary);
      white-space: pre-line;
    }
    
    .no-profile-notice {
      padding: var(--spacing-sm);
      background: #fff3cd;
      border-radius: var(--radius-sm);
      font-size: var(--font-size-sm);
      color: #856404;
      text-align: center;
      margin-bottom: var(--spacing-md);
    }
  </style>
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
          <a href="mypage.php" class="nav-link active">
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
  <main class="mypage-container">
    <div class="container">
      
      <!-- ページヘッダー -->
      <header class="mypage-header fade-in">
        <h2 class="page-title">キャリア相談者マイページ</h2>
        <p class="welcome-text">ようこそ、<strong><?php echo h($client_name); ?></strong> さん</p>
      </header>

      <?php if ($success): ?>
        <div class="alert alert-success fade-in">
          <?php echo h($success); ?>
        </div>
      <?php endif; ?>

      <!-- 承認済み予約（Google Meet） -->
      <?php if (!empty($confirmed_reserves)): ?>
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="video"></i>
              キャリア相談 実施予定（Google Meet）
            </h2>
          </div>
          <div class="card-body">
            <?php foreach ($confirmed_reserves as $reserve): ?>
            <div class="reservation-item confirmed-item">
              <div class="reservation-main">
                <div class="reservation-header">
                  <div class="user-info-header">
                    <span class="role-label role-trainer">【コンサルタント】</span>
                    <span class="student-name-header"><?php echo h($reserve['trainer_name']); ?> さん</span>
                  </div>
                </div>
                
                <?php if ($reserve['consultation_topic']): ?>
                <div class="persona-info">
                  <p class="persona-situation"><strong>相談内容：</strong><?php echo nl2br(h($reserve['consultation_topic'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="reservation-info">
                  <div class="info-row">
                    <i data-lucide="calendar"></i>
                    <span><?php echo h(date('Y年m月d日', strtotime($reserve['meeting_date']))); ?>（<?php echo getJapaneseWeekday($reserve['meeting_date']); ?>）</span>
                  </div>
                  <div class="info-row">
                    <i data-lucide="clock"></i>
                    <span><?php 
                      $start_time = date('H:i', strtotime($reserve['meeting_date']));
                      $end_time = date('H:i', strtotime($reserve['meeting_date'] . ' +1 hour'));
                      echo h($start_time . ' - ' . $end_time);
                    ?></span>
                  </div>
                  <?php if ($reserve['meet_url']): ?>
                  <div class="info-row meet-url-row">
                    <i data-lucide="video"></i>
                    <a href="<?php echo h($reserve['meet_url']); ?>" target="_blank" class="meeting-link meet-button">
                      <i data-lucide="video"></i>
                      Google Meetに参加
                    </a>
                    <button class="btn-icon" onclick="copyToClipboard('<?php echo h($reserve['meet_url']); ?>')" title="URLをコピー">
                      <i data-lucide="copy"></i>
                    </button>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </article>
      </section>
      <?php endif; ?>

      <!-- 承認待ち予約 -->
      <?php if (!empty($pending_reserves)): ?>
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="clock"></i>
              承認待ちの予約
            </h2>
          </div>
          <div class="card-body">
            <?php foreach ($pending_reserves as $reserve): ?>
            <div class="reservation-item pending-item">
              <div class="reservation-main">
                <div class="reservation-header">
                  <div class="user-info-header">
                    <span class="role-label role-trainer">【コンサルタント】</span>
                    <span class="student-name-header"><?php echo h($reserve['trainer_name']); ?> さん</span>
                  </div>
                  <span class="status-badge status-pending">承認待ち</span>
                </div>
                
                <?php if ($reserve['consultation_topic']): ?>
                <div class="persona-info">
                  <p class="persona-situation"><strong>相談内容：</strong><?php echo nl2br(h($reserve['consultation_topic'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="reservation-info">
                  <div class="info-row">
                    <i data-lucide="calendar"></i>
                    <span><?php echo h(date('Y年m月d日', strtotime($reserve['meeting_date']))); ?>（<?php echo getJapaneseWeekday($reserve['meeting_date']); ?>）</span>
                  </div>
                  <div class="info-row">
                    <i data-lucide="clock"></i>
                    <span><?php 
                      $start_time = date('H:i', strtotime($reserve['meeting_date']));
                      $end_time = date('H:i', strtotime($reserve['meeting_date'] . ' +1 hour'));
                      echo h($start_time . ' - ' . $end_time);
                    ?></span>
                  </div>
                  <div class="info-row">
                    <i data-lucide="info"></i>
                    <span class="text-muted">申請日: <?php echo h(date('Y年m月d日', strtotime($reserve['created_at']))); ?></span>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </article>
      </section>
      <?php endif; ?>

      <!-- キャリアコンサルタント一覧 -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="users"></i>
              キャリアコンサルタント一覧
            </h2>
            <p style="margin-top: var(--spacing-sm); color: var(--text-secondary);">
              気になるキャリアコンサルタントを選んで、キャリア相談の予約ができます。
            </p>
          </div>
          <div class="card-body">
            <div class="trainer-grid">
              <?php foreach ($trainers as $trainer): ?>
              <div class="trainer-card">
                <div class="trainer-header">
                  <div class="trainer-avatar">
                    <?php echo mb_substr(h($trainer['nickname'] ?: $trainer['name']), 0, 1); ?>
                  </div>
                  <div class="trainer-name-section">
                    <h3><?php echo h($trainer['nickname'] ?: $trainer['name']); ?></h3>
                    <?php if ($trainer['nickname']): ?>
                      <p class="trainer-real-name">（<?php echo h($trainer['name']); ?>）</p>
                    <?php endif; ?>
                  </div>
                </div>
                
                <?php if ($trainer['career_description']): ?>
                  <div class="trainer-career">
                    <h4>
                      <i data-lucide="briefcase"></i>
                      経歴・専門分野
                    </h4>
                    <p><?php echo h($trainer['career_description']); ?></p>
                  </div>
                <?php endif; ?>
                
                <?php if ($trainer['available_time']): ?>
                  <div class="trainer-availability">
                    <h4>
                      <i data-lucide="clock"></i>
                      対応可能時間
                    </h4>
                    <p><?php echo h($trainer['available_time']); ?></p>
                  </div>
                <?php endif; ?>
                
                <?php if (!$trainer['career_description'] && !$trainer['available_time']): ?>
                  <div class="no-profile-notice">
                    <i data-lucide="info"></i>
                    プロフィール未設定
                  </div>
                <?php endif; ?>
                
                <a href="reserve.php?trainer_id=<?php echo h($trainer['id']); ?>" class="btn-primary btn-block">
                  <i data-lucide="calendar-plus"></i>
                  このコンサルタントに予約する
                </a>
              </div>
              <?php endforeach; ?>
            </div>
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
    
    // URLコピー
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(function() {
        alert('URLをコピーしました');
      }, function(err) {
        console.error('コピーに失敗しました: ', err);
      });
    }
  </script>
</body>
</html>
