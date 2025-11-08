<?php
// 認証チェック
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/database.php';
requireLogin('user');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$success = getSessionMessage('success');
$user_id = $current_user['id'];
$user_name = $current_user['name'];

// 曜日を取得する関数
function getJapaneseWeekday($date) {
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $weekdays[date('w', strtotime($date))];
}

// データベース接続
$db = getDBConnection();

// ユーザーのチケット残数取得
$stmt = $db->prepare("SELECT ticket_count FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
$ticket_count = $user_data['ticket_count'] ?? 5;

// 予約データ取得（完了済み・実施予定）
$stmt = $db->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.status,
        r.meeting_url,
        t.name as trainer_name,
        p.persona_name,
        p.age,
        p.job,
        f.comment as feedback_comment,
        r.created_at
    FROM reserves r
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN personas p ON r.persona_id = p.id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    WHERE r.user_id = ?
    ORDER BY r.meeting_date DESC
");
$stmt->execute([$user_id]);
$all_reservations = $stmt->fetchAll();

// 完了済みセッション（completedまたはフィードバックあり）
$completed_sessions = array_filter($all_reservations, function($r) {
    return $r['status'] === 'completed' || ($r['status'] === 'confirmed' && $r['feedback_comment'] !== null);
});

// 実施予定セッション（confirmedでフィードバックなし）
$upcoming_sessions = array_filter($all_reservations, function($r) {
    return $r['status'] === 'confirmed' && $r['feedback_comment'] === null;
});

// 承認待ちセッション（pending）
$pending_sessions = array_filter($all_reservations, function($r) {
    return $r['status'] === 'pending';
});

// 完了数・進捗計算
$completed_count = count($completed_sessions);
$total_count = 5; // 固定：5回の練習が必要
$remaining_count = $total_count - $completed_count;
$progress_percentage = ($completed_count / $total_count) * 100;

// 5回分の練習セットデータを構築
$practice_set = [];
$completed_index = 0;
for ($i = 1; $i <= 5; $i++) {
    if ($completed_index < count($completed_sessions)) {
        $session = array_values($completed_sessions)[$completed_index];
        $practice_set[] = [
            'id' => $session['id'],
            'date' => date('Y-m-d', strtotime($session['meeting_date'])),
            'consultant' => $session['trainer_name'],
            'feedback' => $session['feedback_comment'] ? '完了' : '未提出',
            'score' => null, // スコアは後で実装
            'completed' => true
        ];
        $completed_index++;
    } else {
        $practice_set[] = [
            'id' => null,
            'date' => null,
            'consultant' => null,
            'feedback' => null,
            'score' => null,
            'completed' => false
        ];
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
          <a href="profile.php" class="nav-link">
            <i data-lucide="user"></i>
            <span>プロフィール</span>
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
        <h2 class="page-title">キャリアコンサルタント受験者マイページ</h2>
        <p class="welcome-text">ようこそ、<strong><?php echo h($user_name); ?></strong> さん</p>
      </header>

      <?php if ($success): ?>
        <div class="alert alert-success fade-in">
          <?= h($success) ?>
        </div>
      <?php endif; ?>

      <!-- 進捗状況カウンター -->
      <section class="progress-section fade-in">
        <div class="progress-card">
          <div class="progress-header">
            <h3 class="progress-title">
              <i data-lucide="target"></i>
              実技試験練習の進捗状況
            </h3>
            <div class="progress-stats">
              <span class="progress-count"><?php echo $completed_count; ?></span>
              <span class="progress-divider">/</span>
              <span class="progress-total"><?php echo $total_count; ?></span>
              <span class="progress-label">回完了</span>
            </div>
          </div>
          
          <!-- 5回の練習チェックポイント -->
          <div class="checkpoint-container">
            <?php 
            $checkpoint_number = 1;
            foreach ($practice_set as $practice): ?>
              <div class="checkpoint-item <?php echo $practice['completed'] ? 'completed' : 'pending'; ?>">
                <div class="checkpoint-circle">
                  <?php if ($practice['completed']): ?>
                    <i data-lucide="check"></i>
                  <?php else: ?>
                    <span class="checkpoint-number"><?php echo $checkpoint_number; ?></span>
                  <?php endif; ?>
                </div>
                <div class="checkpoint-label">
                  <?php if ($practice['completed']): ?>
                    <div class="checkpoint-date"><?php echo date('n/j', strtotime($practice['date'])) . '(' . getJapaneseWeekday($practice['date']) . ')'; ?></div>
                  <?php else: ?>
                    <div class="checkpoint-pending">第<?php echo $checkpoint_number; ?>回</div>
                  <?php endif; ?>
                </div>
                <?php if (!$practice['completed']): ?>
                  <div class="checkpoint-status">
                    <i data-lucide="lock"></i>
                  </div>
                <?php endif; ?>
              </div>
            <?php 
            $checkpoint_number++;
            endforeach; ?>
          </div>

          <!-- 励ましメッセージ -->
          <div class="progress-message">
            <?php if ($completed_count === 0): ?>
              <p class="message-text">🌟 さあ、最初の一歩を踏み出しましょう！</p>
            <?php elseif ($completed_count < 3): ?>
              <p class="message-text">💪 順調です！この調子で頑張りましょう！</p>
            <?php elseif ($completed_count < 5): ?>
              <p class="message-text">🔥 あと少し！ゴールが見えてきました！</p>
            <?php else: ?>
              <p class="message-text">🎉 おめでとうございます！5回の練習を完了しました！</p>
            <?php endif; ?>
          </div>

          <?php if ($completed_count < $total_count): ?>
            <div class="progress-action">
              <a href="mypage/reserve/new.php" class="btn-primary btn-large">
                <i data-lucide="calendar-plus"></i>
                次の練習を予約リクエストをする
              </a>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- マイページグリッド -->
      <section class="mypage-grid">
        
        <!-- 実技試験練習・予約詳細 -->
        <article class="mypage-card card hover-lift fade-in">
          <div class="card-header">
            <div class="card-icon-large">
              <svg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
                <!-- テーブルと2人のイラスト -->
                <rect x="20" y="45" width="40" height="20" fill="none" stroke="#2C3E50" stroke-width="2"/>
                <!-- 人物1 -->
                <circle cx="30" cy="30" r="6" fill="none" stroke="#2C3E50" stroke-width="1.5"/>
                <path d="M 25 36 Q 30 33 35 36" fill="none" stroke="#2C3E50" stroke-width="1.5"/>
                <!-- 人物2 -->
                <circle cx="50" cy="30" r="6" fill="none" stroke="#2C3E50" stroke-width="1.5"/>
                <path d="M 45 36 Q 50 33 55 36" fill="none" stroke="#2C3E50" stroke-width="1.5"/>
                <!-- ノートPC -->
                <rect x="45" y="42" width="10" height="8" fill="none" stroke="#2C3E50" stroke-width="1.5"/>
              </svg>
            </div>
            <h3>実技試験練習・<br>予約詳細</h3>
          </div>
          <div class="card-content">
            <!-- 承認待ちの予約 -->
            <?php if (count($pending_sessions) > 0): ?>
              <div class="section-divider" style="margin-bottom: var(--spacing-md);">
                <h4 class="section-subtitle" style="color: var(--color-warning);">
                  <i data-lucide="clock"></i>
                  承認待ちの予約
                </h4>
              </div>
              <div class="session-list" style="margin-bottom: var(--spacing-xl);">
                <?php foreach ($pending_sessions as $session): ?>
                  <div class="session-item">
                    <div class="session-date">
                      <i data-lucide="calendar"></i>
                      <span><?php echo date('n月j日', strtotime($session['meeting_date'])) . '(' . getJapaneseWeekday($session['meeting_date']) . ')'; ?></span>
                    </div>
                    <div class="session-info">
                      <div class="session-time"><?php echo date('H:i', strtotime($session['meeting_date'])); ?> 〜</div>
                      <div class="session-consultant">
                        <i data-lucide="user"></i>
                        <?php echo h($session['trainer_name']); ?>
                      </div>
                    </div>
                    <span class="badge" style="background-color: var(--color-warning); color: white;">承認待ち</span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- 確定済みの予約 -->
            <div class="section-divider">
              <h4 class="section-subtitle">
                <i data-lucide="calendar-clock"></i>
                予定されている練習（確定済み）
              </h4>
            </div>
            
            <?php if (count($upcoming_sessions) > 0): ?>
              <div class="session-list">
                <?php foreach ($upcoming_sessions as $session): ?>
                  <div class="session-item">
                    <div class="session-date">
                      <i data-lucide="calendar"></i>
                      <span><?php echo date('n月j日', strtotime($session['meeting_date'])) . '(' . getJapaneseWeekday($session['meeting_date']) . ')'; ?></span>
                    </div>
                    <div class="session-info">
                      <div class="session-time"><?php echo date('H:i', strtotime($session['meeting_date'])); ?> 〜</div>
                      <div class="session-consultant">
                        <i data-lucide="user"></i>
                        <?php echo h($session['trainer_name']); ?>
                      </div>
                    </div>
                    <span class="badge badge-success">確定</span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="empty-message">確定済みの予約はありません</p>
            <?php endif; ?>

            <!-- 過去の練習履歴とフィードバック -->
            <div class="section-divider" style="margin-top: var(--spacing-xl);">
              <h4 class="section-subtitle">
                <i data-lucide="history"></i>
                過去の練習履歴・フィードバック
              </h4>
            </div>
            
            <?php if (count($completed_sessions) > 0): ?>
              <div class="history-list">
                <?php foreach (array_slice(array_values($completed_sessions), 0, 3) as $session): ?>
                  <div class="history-item">
                    <div class="history-header">
                      <span class="history-date">
                        <i data-lucide="calendar"></i>
                        <?php echo date('Y/n/j', strtotime($session['meeting_date'])) . '(' . getJapaneseWeekday($session['meeting_date']) . ')'; ?>
                      </span>
                      <?php if ($session['feedback_comment']): ?>
                        <span class="badge badge-success">レポート受領済み</span>
                      <?php else: ?>
                        <span class="badge" style="background-color: #ccc;">レポート未提出</span>
                      <?php endif; ?>
                    </div>
                    <div class="history-consultant">
                      <i data-lucide="user"></i>
                      <?php echo h($session['trainer_name']); ?>
                    </div>
                    <?php if ($session['feedback_comment']): ?>
                      <div class="history-actions" style="margin-top: var(--spacing-sm);">
                        <a href="mypage/reserve/feedback/view.php?id=<?php echo $session['id']; ?>" class="btn-primary btn-small">
                          <i data-lucide="file-text"></i>
                          フィードバックを見る
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="empty-message">練習履歴がありません</p>
            <?php endif; ?>
          </div>
          <div class="card-footer">
            <a href="mypage/reserve.php" class="btn-secondary btn-block">
              <i data-lucide="calendar"></i>
              すべての予約を見る
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
