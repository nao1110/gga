<?php
// セッション開始（実際の実装では認証チェックを行う）
session_start();

// 曜日を取得する関数
function getJapaneseWeekday($date) {
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $weekdays[date('w', strtotime($date))];
}

// ダミーデータ（実際はデータベースから取得）
$trainer_name = "田中 美咲";
$trainer_specialty = "企業内キャリア形成、転職支援";

// 予約承認待ちリスト
$pending_reservations = [
    [
        'id' => 1,
        'student_name' => '山田 太郎',
        'practice_number' => 4,
        'requested_date' => '2025-11-15',
        'requested_time' => '14:00-15:00',
        'request_date' => '2025-11-01',
        'status' => 'pending'
    ],
    [
        'id' => 2,
        'student_name' => '佐藤 花子',
        'practice_number' => 2,
        'requested_date' => '2025-11-18',
        'requested_time' => '10:00-11:00',
        'request_date' => '2025-11-01',
        'status' => 'pending'
    ]
];

// 承認済み・実施予定の予約
$confirmed_reservations = [
    [
        'id' => 3,
        'student_name' => '鈴木 一郎',
        'practice_number' => 3,
        'date' => '2025-11-08',
        'time' => '15:00-16:00',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        'status' => 'confirmed',
        'feedback_submitted' => false
    ],
    [
        'id' => 4,
        'student_name' => '高橋 さくら',
        'practice_number' => 1,
        'date' => '2025-11-12',
        'time' => '13:00-14:00',
        'meeting_url' => 'https://meet.google.com/xyz-abcd-efg',
        'status' => 'confirmed',
        'feedback_submitted' => false
    ]
];

// 完了済み（フィードバック入力済み）
$completed_sessions = [
    [
        'id' => 5,
        'student_name' => '伊藤 誠',
        'practice_number' => 5,
        'date' => '2025-10-25',
        'time' => '10:00-11:00',
        'status' => 'completed',
        'feedback_submitted' => true,
        'feedback_date' => '2025-10-25'
    ],
    [
        'id' => 6,
        'student_name' => '山田 太郎',
        'practice_number' => 3,
        'date' => '2025-10-20',
        'time' => '14:00-15:00',
        'status' => 'completed',
        'feedback_submitted' => true,
        'feedback_date' => '2025-10-20'
    ],
    [
        'id' => 7,
        'student_name' => '佐藤 花子',
        'practice_number' => 1,
        'date' => '2025-10-15',
        'time' => '16:00-17:00',
        'status' => 'completed',
        'feedback_submitted' => true,
        'feedback_date' => '2025-10-15'
    ]
];
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
              <?php echo htmlspecialchars($trainer_name); ?>
            </span>
            <a href="../index.php" class="btn-secondary btn-small">
              ログアウト
            </a>
          </div>
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
                  <span class="practice-badge">練習<?php echo $reservation['practice_number']; ?>回目</span>
                  <span class="student-name-header"><?php echo htmlspecialchars($reservation['student_name']); ?> さん</span>
                </div>
                <div class="reservation-info">
                  <div class="info-row">
                    <i data-lucide="calendar"></i>
                    <span><?php echo date('Y年m月d日', strtotime($reservation['requested_date'])); ?>（<?php echo getJapaneseWeekday($reservation['requested_date']); ?>） <?php echo $reservation['requested_time']; ?></span>
                  </div>
                  <div class="info-row">
                    <i data-lucide="clock"></i>
                    <span class="text-muted">申請日: <?php echo date('m月d日', strtotime($reservation['request_date'])); ?></span>
                  </div>
                </div>
              </div>
              <div class="reservation-actions">
                <button class="btn-success btn-small" onclick="approveReservation(<?php echo $reservation['id']; ?>)">
                  <i data-lucide="check"></i>
                  承認
                </button>
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
                  <span class="practice-badge">練習<?php echo $reservation['practice_number']; ?>回目</span>
                  <span class="student-name-header"><?php echo htmlspecialchars($reservation['student_name']); ?> さん</span>
                </div>
                <div class="reservation-info">
                  <div class="info-row">
                    <i data-lucide="calendar"></i>
                    <span><?php echo date('Y年m月d日', strtotime($reservation['date'])); ?>（<?php echo getJapaneseWeekday($reservation['date']); ?>） <?php echo $reservation['time']; ?></span>
                  </div>
                  <div class="info-row">
                    <i data-lucide="video"></i>
                    <a href="<?php echo htmlspecialchars($reservation['meeting_url']); ?>" target="_blank" class="meeting-link">
                      <?php echo htmlspecialchars($reservation['meeting_url']); ?>
                    </a>
                    <button class="btn-icon" onclick="copyToClipboard('<?php echo htmlspecialchars($reservation['meeting_url']); ?>')" title="URLをコピー">
                      <i data-lucide="copy"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="reservation-actions">
                <a href="mypage/reserve/feedback/input.php?id=<?php echo $reservation['id']; ?>" class="btn-primary btn-small">
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
                    <span class="practice-badge completed">練習<?php echo $session['practice_number']; ?>回目</span>
                    <span class="student-name-header"><?php echo htmlspecialchars($session['student_name']); ?> さん</span>
                  </div>
                  <div class="reservation-info">
                    <div class="info-row">
                      <i data-lucide="calendar"></i>
                      <span><?php echo date('Y年m月d日', strtotime($session['date'])); ?>（<?php echo getJapaneseWeekday($session['date']); ?>） <?php echo $session['time']; ?></span>
                    </div>
                    <div class="info-row">
                      <i data-lucide="check-circle"></i>
                      <span class="text-success">レポート提出済み（<?php echo date('m月d日', strtotime($session['feedback_date'])); ?>）</span>
                    </div>
                  </div>
                </div>
                <div class="reservation-actions">
                  <a href="mypage/reserve/feedback/input.php?id=<?php echo $session['id']; ?>" class="btn-secondary btn-small">
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

    // 予約承認
    function approveReservation(id) {
      if (confirm('この予約を承認しますか？')) {
        // 実際はAjaxでサーバーに送信
        alert('予約ID ' + id + ' を承認しました。');
        location.reload();
      }
    }

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
