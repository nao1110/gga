<?php
// 認証チェック
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/database.php';
requireLogin('trainer');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$success = getSessionMessage('success');

// 曜日を取得する関数
function getJapaneseWeekday($date) {
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $weekdays[date('w', strtotime($date))];
}

// データベース接続
$pdo = getDBConnection();

// トレーナー情報の取得
$trainer_name = $current_user['name'];
$trainer_specialty = $current_user['specialty'] ?? "キャリア形成支援";

// 予約データを取得（承認待ち・確定済み・完了済み）
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.user_id,
        r.meeting_date,
        r.meeting_url,
        r.status,
        r.created_at,
        u.name as user_name,
        u.email as user_email,
        p.id as persona_id,
        p.persona_name as persona_name,
        f.id as feedback_id,
        f.created_at as feedback_date
    FROM reserves r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN personas p ON r.persona_id = p.id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    WHERE r.trainer_id = ?
    ORDER BY r.meeting_date ASC
");
$stmt->execute([$current_user['id']]);
$all_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ステータスごとに分類
$pending_reservations = [];
$confirmed_reservations = [];
$completed_sessions = [];

foreach ($all_reservations as $reservation) {
    if ($reservation['status'] === 'pending') {
        $pending_reservations[] = $reservation;
    } elseif ($reservation['status'] === 'confirmed') {
        $confirmed_reservations[] = $reservation;
    } elseif ($reservation['status'] === 'completed') {
        $completed_sessions[] = $reservation;
    }
}

// 完了セッションは最新3件のみ表示
$completed_sessions = array_slice($completed_sessions, 0, 3);
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
</head>
<body>
  <!-- メインコンテナ -->
  <main class="hero-container">
    <div class="container">
      
      <!-- ヘッダー -->
      <header class="page-header fade-in">
        <div class="header-content">
          <div>
            <h1 class="logo-primary">CareerTre</h1>
            <p class="hero-tagline">-キャリトレ-</p>
          </div>
                    <div class="header-actions">
            <span class="user-name">
              <i data-lucide="graduation-cap"></i>
              <?php echo h($trainer_name); ?>
            </span>
            <a href="../../controller/logout.php" class="btn-secondary btn-small">
              ログアウト
            </a>
          </div>
        </div>
      </div>
    </header>

    <!-- メインコンテナ -->
    <main class="mypage-container">
      <div class="container">
        
        <?php if ($success): ?>
          <div class="alert alert-success fade-in">
            <?= h($success) ?>
          </div>
        <?php endif; ?>
        </div>
      </header>

      <!-- 予約承認待ち -->
      <?php if (!empty($pending_reservations)): ?>
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="bell"></i>
              実技練習予約の承認
            </h2>
          </div>
          <div class="card-body">
            <?php foreach ($pending_reservations as $reservation): ?>
            <div class="reservation-item pending-item">
              <div class="reservation-main">
                <div class="reservation-header">
                  <span class="student-name-header"><?php echo h($reservation['user_name']); ?> さん</span>
                </div>
                <div class="reservation-info">
                  <div class="info-row">
                    <i data-lucide="calendar"></i>
                    <span><?php echo h(date('Y年m月d日', strtotime($reservation['meeting_date']))); ?>（<?php echo getJapaneseWeekday($reservation['meeting_date']); ?>） <?php echo h(date('H:i', strtotime($reservation['meeting_date']))); ?></span>
                  </div>
                  <div class="info-row">
                    <i data-lucide="clock"></i>
                    <span class="text-muted">申請日: <?php echo h(date('m月d日', strtotime($reservation['created_at']))); ?></span>
                  </div>
                </div>
              </div>
              <div class="reservation-actions">
                <form action="../../controller/trainer/reserve_approve.php" method="POST" style="display: inline;">
                  <input type="hidden" name="reserve_id" value="<?php echo h($reservation['id']); ?>">
                  <button type="submit" class="btn-success btn-small" onclick="return confirm('この予約を承認しますか？');">
                    <i data-lucide="check"></i>
                    承認
                  </button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </article>
      </section>
      <?php endif; ?>

      <!-- 実施予定の面接練習 -->
      <?php if (!empty($confirmed_reservations)): ?>
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="calendar-days"></i>
              実施予定の実技練習（オンラインURL）
            </h2>
          </div>
          <div class="card-body">
            <?php foreach ($confirmed_reservations as $reservation): ?>
            <div class="reservation-item confirmed-item">
              <div class="reservation-main">
                <div class="reservation-header">
                  <span class="student-name-header"><?php echo h($reservation['user_name']); ?> さん</span>
                </div>
                <div class="reservation-info">
                  <div class="info-row">
                    <i data-lucide="calendar"></i>
                    <span><?php echo h(date('Y年m月d日', strtotime($reservation['meeting_date']))); ?>（<?php echo getJapaneseWeekday($reservation['meeting_date']); ?>） <?php echo h(date('H:i', strtotime($reservation['meeting_date']))); ?></span>
                  </div>
                  <?php if ($reservation['meeting_url']): ?>
                  <div class="info-row">
                    <i data-lucide="video"></i>
                    <a href="<?php echo h($reservation['meeting_url']); ?>" target="_blank" class="meeting-link">
                      <?php echo h($reservation['meeting_url']); ?>
                    </a>
                    <button class="btn-icon" onclick="copyToClipboard('<?php echo h($reservation['meeting_url']); ?>')" title="URLをコピー">
                      <i data-lucide="copy"></i>
                    </button>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="reservation-actions">
                <a href="mypage/reserve/feedback/input.php?id=<?php echo h($reservation['id']); ?>" class="btn-primary btn-small">
                  <i data-lucide="file-edit"></i>
                  レポート入力
                </a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </article>
      </section>
      <?php endif; ?>

      <!-- 完了済み・フィードバック履歴 -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="file-text"></i>
              完了済み・レポート提出履歴
            </h2>
          </div>
          <div class="card-body">
            <?php if (!empty($completed_sessions)): ?>
              <?php foreach ($completed_sessions as $session): ?>
              <div class="reservation-item completed-item">
                <div class="reservation-main">
                  <div class="reservation-header">
                    <span class="student-name-header"><?php echo h($session['user_name']); ?> さん</span>
                  </div>
                  <div class="reservation-info">
                    <div class="info-row">
                      <i data-lucide="calendar"></i>
                      <span><?php echo h(date('Y年m月d日', strtotime($session['meeting_date']))); ?>（<?php echo getJapaneseWeekday($session['meeting_date']); ?>） <?php echo h(date('H:i', strtotime($session['meeting_date']))); ?></span>
                    </div>
                    <?php if ($session['feedback_id']): ?>
                    <div class="info-row">
                      <i data-lucide="check-circle"></i>
                      <span class="text-success">レポート提出済み（<?php echo h(date('m月d日', strtotime($session['feedback_date']))); ?>）</span>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="reservation-actions">
                  <a href="mypage/reserve/feedback/input.php?id=<?php echo h($session['id']); ?>" class="btn-secondary btn-small">
                    <i data-lucide="eye"></i>
                    レポート確認
                  </a>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="empty-message">まだ完了したセッションはありません。</p>
            <?php endif; ?>
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
