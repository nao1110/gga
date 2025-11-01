<?php
// セッション開始（実際の実装では認証チェックを行う）
session_start();

// 曜日を取得する関数
function getJapaneseWeekday($date) {
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $weekdays[date('w', strtotime($date))];
}

// ダミーデータ（実際はデータベースから取得）
$user_name = "山田 太郎";

// 5回の練習セット（1セット = 5回）
$practice_set = [
    [
        'id' => 1,
        'date' => '2025-10-05',
        'consultant' => '田中 美咲',
        'feedback' => '初回にしては良好です',
        'score' => 65,
        'completed' => true
    ],
    [
        'id' => 2,
        'date' => '2025-10-12',
        'consultant' => '佐藤 花子',
        'feedback' => '傾聴姿勢が改善されました',
        'score' => 72,
        'completed' => true
    ],
    [
        'id' => 3,
        'date' => '2025-10-20',
        'consultant' => '鈴木 一郎',
        'feedback' => '質問の組み立てが向上しています',
        'score' => 78,
        'completed' => true
    ],
    [
        'id' => 4,
        'date' => null,
        'consultant' => null,
        'feedback' => null,
        'score' => null,
        'completed' => false
    ],
    [
        'id' => 5,
        'date' => null,
        'consultant' => null,
        'feedback' => null,
        'score' => null,
        'completed' => false
    ]
];

// 完了数を計算
$completed_count = count(array_filter($practice_set, function($item) {
    return $item['completed'];
}));
$total_count = 5;
$progress_percentage = ($completed_count / $total_count) * 100;

$upcoming_sessions = [
    [
        'date' => '2025-11-15',
        'time' => '14:00-15:30',
        'consultant' => '佐藤 花子',
        'status' => '確定'
    ],
    [
        'date' => '2025-11-22',
        'time' => '10:00-11:30',
        'consultant' => '鈴木 一郎',
        'status' => '確定'
    ]
];

$past_sessions = [
    [
        'date' => '2025-10-20',
        'consultant' => '田中 美咲',
        'feedback' => '傾聴スキルが向上しています',
        'score' => 85
    ],
    [
        'date' => '2025-10-10',
        'consultant' => '佐藤 花子',
        'feedback' => '質問の組み立てが良好です',
        'score' => 78
    ],
    [
        'date' => '2025-09-25',
        'consultant' => '鈴木 一郎',
        'feedback' => '基本スキルの向上が必要',
        'score' => 72
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
          <a href="logout.php" class="nav-link">
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
        <p class="welcome-text">ようこそ、<strong><?php echo htmlspecialchars($user_name); ?></strong> さん</p>
      </header>

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
            <?php foreach ($practice_set as $practice): ?>
              <div class="checkpoint-item <?php echo $practice['completed'] ? 'completed' : 'pending'; ?>">
                <div class="checkpoint-circle">
                  <?php if ($practice['completed']): ?>
                    <i data-lucide="check"></i>
                  <?php else: ?>
                    <span class="checkpoint-number"><?php echo $practice['id']; ?></span>
                  <?php endif; ?>
                </div>
                <div class="checkpoint-label">
                  <?php if ($practice['completed']): ?>
                    <div class="checkpoint-date"><?php echo date('n/j', strtotime($practice['date'])) . '(' . getJapaneseWeekday($practice['date']) . ')'; ?></div>
                  <?php else: ?>
                    <div class="checkpoint-pending">第<?php echo $practice['id']; ?>回</div>
                  <?php endif; ?>
                </div>
                <?php if (!$practice['completed']): ?>
                  <div class="checkpoint-status">
                    <i data-lucide="lock"></i>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
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
            <!-- 予定されている練習 -->
            <div class="section-divider">
              <h4 class="section-subtitle">
                <i data-lucide="calendar-clock"></i>
                予定されている練習
              </h4>
            </div>
            
            <?php if (count($upcoming_sessions) > 0): ?>
              <div class="session-list">
                <?php foreach ($upcoming_sessions as $session): ?>
                  <div class="session-item">
                    <div class="session-date">
                      <i data-lucide="calendar"></i>
                      <span><?php echo date('n月j日', strtotime($session['date'])) . '(' . getJapaneseWeekday($session['date']) . ')'; ?></span>
                    </div>
                    <div class="session-info">
                      <div class="session-time"><?php echo $session['time']; ?></div>
                      <div class="session-consultant">
                        <i data-lucide="user"></i>
                        <?php echo htmlspecialchars($session['consultant']); ?>
                      </div>
                    </div>
                    <span class="badge badge-success"><?php echo $session['status']; ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="empty-message">予定されている練習はありません</p>
            <?php endif; ?>

            <!-- 過去の練習履歴 -->
            <div class="section-divider" style="margin-top: var(--spacing-xl);">
              <h4 class="section-subtitle">
                <i data-lucide="history"></i>
                過去の練習履歴
              </h4>
            </div>
            
            <?php if (count($past_sessions) > 0): ?>
              <div class="history-list">
                <?php foreach (array_slice($past_sessions, 0, 3) as $session): ?>
                  <div class="history-item">
                    <div class="history-header">
                      <span class="history-date">
                        <i data-lucide="calendar"></i>
                        <?php echo date('Y/n/j', strtotime($session['date'])) . '(' . getJapaneseWeekday($session['date']) . ')'; ?>
                      </span>
                    </div>
                    <div class="history-consultant">
                      <i data-lucide="user"></i>
                      <?php echo htmlspecialchars($session['consultant']); ?>
                    </div>
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
