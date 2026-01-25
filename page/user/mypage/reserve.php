<?php
// 認証チェック
require_once __DIR__ . '/../../../lib/validation.php';
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/database.php';
requireLogin('user');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$user_id = $current_user['id'];
$user_name = $current_user['name'];

// データベース接続
$db = getDBConnection();

// 予約一覧データ取得（未来の予約のみ、承認待ち・確定済み）
$stmt = $db->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.status,
        r.meeting_url,
        r.created_at,
        t.name as trainer_name,
        p.persona_name
    FROM reserves r
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN personas p ON r.persona_id = p.id
    WHERE r.user_id = ? 
    AND r.status IN ('pending', 'confirmed')
    ORDER BY r.meeting_date ASC
");
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll();

// ステータス別に分類
$confirmed_reservations = array_filter($reservations, function($r) {
    return $r['status'] === 'confirmed';
});
$pending_reservations = array_filter($reservations, function($r) {
    return $r['status'] === 'pending';
});
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>予約一覧 - CareerTre キャリアトレーナーズ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../../assets/css/variables.css">
  <link rel="stylesheet" href="../../../assets/css/custom.css">
</head>
<body>
  <!-- ナビゲーションヘッダー -->
  <nav class="navbar">
    <div class="container">
      <div class="navbar-content">
        <div class="navbar-brand">
          <h1 class="logo-primary" style="margin: 0; font-size: var(--font-size-xl);">CareerTre</h1>
          <span class="navbar-tagline">-キャリアトレーナーズ-</span>
        </div>
        <div class="navbar-menu">
          <a href="../mypage.php" class="nav-link">
            <i data-lucide="home"></i>
            <span>マイページ</span>
          </a>
          <a href="../profile.php" class="nav-link">
            <i data-lucide="user"></i>
            <span>プロフィール</span>
          </a>
          <a href="../logout.php" class="nav-link">
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
      <header class="page-header fade-in">
        <a href="../mypage.php" class="back-link">
          <i data-lucide="arrow-left"></i>
          マイページに戻る
        </a>
        <h2 class="page-title">予約一覧</h2>
        <p class="page-description">実技試験練習の予約状況を確認できます</p>
      </header>

      <!-- 新規予約ボタン -->
      <section class="action-section fade-in">
        <a href="reserve/new.php" class="btn-primary btn-large">
          <i data-lucide="calendar-plus"></i>
          新しい予約を追加
        </a>
      </section>

      <!-- 確定済み予約 -->
      <?php if (count($confirmed_reservations) > 0): ?>
      <section class="reservation-section fade-in">
        <div class="section-header">
          <h3 class="section-title">
            <i data-lucide="check-circle"></i>
            確定済みの予約
          </h3>
          <span class="badge badge-success"><?php echo count($confirmed_reservations); ?>件</span>
        </div>

        <div class="reservation-list">
          <?php foreach ($confirmed_reservations as $reservation): ?>
            <article class="reservation-card confirmed">
              <div class="reservation-header">
                <div class="reservation-status">
                  <span class="status-badge status-confirmed">
                    <i data-lucide="check-circle"></i>
                    確定済み
                  </span>
                </div>
                <div class="reservation-id">ID: <?php echo $reservation['id']; ?></div>
              </div>

              <div class="reservation-body">
                <div class="reservation-datetime">
                  <div class="datetime-main">
                    <i data-lucide="calendar"></i>
                    <span class="date-text">
                      <?php 
                        $date = new DateTime($reservation['meeting_date']);
                        echo $date->format('Y年n月j日');
                      ?>
                      （<?php 
                        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                        echo $weekdays[$date->format('w')];
                      ?>）
                    </span>
                  </div>
                  <div class="datetime-time">
                    <i data-lucide="clock"></i>
                    <span class="time-text">
                      <?php echo $date->format('H:i'); ?> 〜
                    </span>
                  </div>
                </div>

                <div class="reservation-consultant">
                  <i data-lucide="user"></i>
                  <div class="consultant-info">
                    <div class="consultant-label">担当コンサルタント</div>
                    <div class="consultant-name"><?php echo h($reservation['trainer_name']); ?></div>
                  </div>
                </div>

                <div class="reservation-meeting">
                  <i data-lucide="video"></i>
                  <div class="meeting-info">
                    <div class="meeting-label">オンライン面接URL</div>
                    <div class="meeting-url-container">
                      <input 
                        type="text" 
                        class="meeting-url-input" 
                        value="<?php echo h($reservation['meeting_url']); ?>" 
                        readonly
                        id="url-<?php echo $reservation['id']; ?>"
                      >
                      <button 
                        class="btn-copy" 
                        onclick="copyToClipboard('url-<?php echo $reservation['id']; ?>')"
                        title="URLをコピー"
                      >
                        <i data-lucide="copy"></i>
                      </button>
                    </div>
                    <a 
                      href="<?php echo htmlspecialchars($reservation['meeting_url']); ?>" 
                      target="_blank" 
                      class="btn-join"
                    >
                      <i data-lucide="external-link"></i>
                      面接ルームに参加
                    </a>
                  </div>
                </div>
              </div>

              <div class="reservation-footer">
                <div class="reservation-meta">
                  <span class="meta-item">
                    <i data-lucide="calendar-check"></i>
                    確定日時: <?php echo date('n/j H:i', strtotime($reservation['confirmed_at'])); ?>
                  </span>
                </div>
                <div class="reservation-actions">
                  <a href="reserve/detail.php?id=<?php echo $reservation['id']; ?>" class="btn-action btn-detail">
                    <i data-lucide="file-text"></i>
                    詳細を見る
                  </a>
                  <button class="btn-action btn-cancel" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                    <i data-lucide="x-circle"></i>
                    キャンセル
                  </button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- リクエスト中の予約 -->
      <?php if (count($pending_reservations) > 0): ?>
      <section class="reservation-section fade-in">
        <div class="section-header">
          <h3 class="section-title">
            <i data-lucide="clock"></i>
            リクエスト中
          </h3>
          <span class="badge badge-warning"><?php echo count($pending_reservations); ?>件</span>
        </div>

        <div class="reservation-list">
          <?php foreach ($pending_reservations as $reservation): ?>
            <article class="reservation-card pending">
              <div class="reservation-header">
                <div class="reservation-status">
                  <span class="status-badge status-pending">
                    <i data-lucide="clock"></i>
                    リクエスト中
                  </span>
                </div>
                <div class="reservation-id">ID: <?php echo h($reservation['id']); ?></div>
              </div>

              <div class="reservation-body">
                <div class="reservation-datetime">
                  <div class="datetime-main">
                    <i data-lucide="calendar"></i>
                    <span class="date-text">
                      <?php 
                        $date = new DateTime($reservation['meeting_date']);
                        echo $date->format('Y年n月j日');
                      ?>
                      （<?php 
                        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                        echo $weekdays[$date->format('w')];
                      ?>）
                    </span>
                  </div>
                  <div class="datetime-time">
                    <i data-lucide="clock"></i>
                    <span class="time-text">
                      <?php echo h($date->format('H:i')); ?>
                    </span>
                  </div>
                </div>

                <div class="reservation-pending-info">
                  <i data-lucide="info"></i>
                  <p>キャリアコンサルタントの確定をお待ちください。確定次第、メールでお知らせします。</p>
                </div>
              </div>

              <div class="reservation-footer">
                <div class="reservation-meta">
                  <span class="meta-item">
                    <i data-lucide="send"></i>
                    申請日時: <?php echo h(date('n/j H:i', strtotime($reservation['created_at']))); ?>
                  </span>
                </div>
                <div class="reservation-actions">
                  <a href="reserve/detail.php?id=<?php echo h($reservation['id']); ?>" class="btn-action btn-detail">
                    <i data-lucide="file-text"></i>
                    詳細を見る
                  </a>
                  <button class="btn-action btn-cancel" onclick="cancelReservation(<?php echo h($reservation['id']); ?>)">
                    <i data-lucide="x-circle"></i>
                    キャンセル
                  </button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- 予約がない場合 -->
      <?php if (count($reservations) === 0): ?>
      <section class="empty-section fade-in">
        <div class="empty-content">
          <div class="empty-icon">
            <i data-lucide="calendar-x"></i>
          </div>
          <h3 class="empty-title">予約がありません</h3>
          <p class="empty-description">実技試験練習の予約をして、スキルアップを目指しましょう！</p>
          <a href="reserve/new.php" class="btn-primary btn-large">
            <i data-lucide="calendar-plus"></i>
            最初の予約をする
          </a>
        </div>
      </section>
      <?php endif; ?>

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

    // URLをクリップボードにコピー
    function copyToClipboard(elementId) {
      const input = document.getElementById(elementId);
      input.select();
      input.setSelectionRange(0, 99999); // モバイル対応
      
      navigator.clipboard.writeText(input.value).then(function() {
        // コピー成功のフィードバック
        const button = event.target.closest('.btn-copy');
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i data-lucide="check"></i>';
        lucide.createIcons();
        
        setTimeout(function() {
          button.innerHTML = originalHTML;
          lucide.createIcons();
        }, 2000);
      }).catch(function(err) {
        alert('コピーに失敗しました');
      });
    }

    // 予約キャンセル
    function cancelReservation(reservationId) {
      if (confirm('この予約をキャンセルしてもよろしいですか？')) {
        // 実際の実装ではここでサーバーに送信
        alert('予約ID ' + reservationId + ' をキャンセルしました');
        location.reload();
      }
    }
  </script>
</body>
</html>
