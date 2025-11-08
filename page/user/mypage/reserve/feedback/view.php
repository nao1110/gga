<?php
// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

// 認証チェック
require_once __DIR__ . '/../../../../../lib/validation.php';
require_once __DIR__ . '/../../../../../lib/auth.php';
require_once __DIR__ . '/../../../../../lib/helpers.php';
require_once __DIR__ . '/../../../../../lib/database.php';
requireLogin('user');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$user_id = $current_user['id'];

// 予約IDを取得
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reservation_id) {
    redirect('/gs_code/gga/page/user/mypage.php');
    exit;
}

// 曜日を取得する関数
function getJapaneseWeekday($date) {
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    return $weekdays[date('w', strtotime($date))];
}

// データベース接続
$pdo = getDBConnection();

// 予約詳細とフィードバック情報を取得
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.meeting_url,
        r.status,
        t.name as trainer_name,
        t.email as trainer_email,
        f.id as feedback_id,
        f.comment as feedback_comment,
        f.created_at as feedback_date
    FROM reserves r
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$reservation_id, $user_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

// 予約が存在しない、または自分の予約でない場合はリダイレクト
if (!$reservation) {
    redirect('/gs_code/gga/page/user/mypage.php');
    exit;
}

// フィードバックが存在しない場合もリダイレクト
if (!$reservation['feedback_comment']) {
    redirect('/gs_code/gga/page/user/mypage.php');
    exit;
}

// フィードバックデータの解析
$feedback_json = json_decode($reservation['feedback_comment'], true);
$feedback_data = [
    'attitude_comment' => $feedback_json['attitude_comment'] ?? '',
    'problem_comment' => $feedback_json['problem_comment'] ?? '',
    'development_comment' => $feedback_json['development_comment'] ?? '',
    'next_advice' => $feedback_json['next_advice'] ?? '',
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>フィードバック詳細 - CareerTre キャリトレ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../../../../assets/css/variables.css">
  <link rel="stylesheet" href="../../../../../assets/css/custom.css">
  
  <style>
    .feedback-section {
      background: white;
      border-radius: 8px;
      padding: var(--spacing-lg);
      margin-bottom: var(--spacing-lg);
      border-left: 4px solid var(--color-primary);
    }
    
    .feedback-section.strengths {
      border-left-color: #22c55e;
    }
    
    .feedback-section.improvements {
      border-left-color: #f59e0b;
    }
    
    .feedback-section.goals {
      border-left-color: #3b82f6;
    }
    
    .feedback-section h3 {
      margin-bottom: var(--spacing-md);
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
    }
    
    .evaluation-criteria {
      background-color: #f8f9fa;
      border-left: 4px solid #6c757d;
      padding: var(--spacing-md);
      margin-bottom: var(--spacing-md);
      border-radius: var(--radius-md);
      font-size: 0.9em;
    }
    
    .evaluation-criteria p {
      margin: 0 0 var(--spacing-xs) 0;
      font-weight: 600;
    }
    
    .evaluation-criteria ul {
      margin: 0;
      padding-left: var(--spacing-lg);
      list-style-type: disc;
    }
    
    .evaluation-criteria li {
      margin-bottom: var(--spacing-xs);
      line-height: 1.5;
    }
    
    .feedback-content {
      line-height: 1.8;
      color: var(--text-primary);
    }
    
    .feedback-content p {
      margin: 0;
    }
    
    .feedback-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .feedback-list li {
      padding: var(--spacing-sm) var(--spacing-md);
      margin-bottom: var(--spacing-sm);
      background: var(--color-bg-secondary);
      border-radius: 4px;
      line-height: 1.6;
    }
    
    .feedback-list li:before {
      content: "✓ ";
      color: var(--color-primary);
      font-weight: bold;
      margin-right: var(--spacing-xs);
    }
    
    .feedback-section.improvements .feedback-list li:before {
      content: "→ ";
      color: #f59e0b;
    }
    
    .feedback-section.goals .feedback-list li:before {
      content: "🎯 ";
    }
    
    .overall-comment {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: var(--spacing-xl);
      border-radius: 8px;
      margin-top: var(--spacing-xl);
    }
    
    .overall-comment h3 {
      color: white;
      margin-bottom: var(--spacing-md);
    }
    
    .overall-comment p {
      line-height: 1.8;
      font-size: 1.05rem;
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
          <a href="../../../mypage.php" class="nav-link">
            <i data-lucide="home"></i>
            <span>マイページ</span>
          </a>
          <a href="../../../profile.php" class="nav-link">
            <i data-lucide="user"></i>
            <span>プロフィール</span>
          </a>
          <a href="../../../../../controller/logout.php" class="nav-link">
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
        <a href="../../../mypage.php" class="back-link">
          <i data-lucide="arrow-left"></i>
          マイページに戻る
        </a>
        <h1 class="logo-primary">CareerTre</h1>
        <p class="hero-tagline">-キャリトレ-</p>
      </header>

      <!-- 実技練習情報 -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="calendar"></i>
              実技練習情報
            </h2>
          </div>
          <div class="card-body">
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">
                  <i data-lucide="calendar"></i>
                  実施日時
                </div>
                <div class="info-value">
                  <?php echo h(date('Y年m月d日', strtotime($reservation['meeting_date']))); ?>（<?php echo getJapaneseWeekday($reservation['meeting_date']); ?>） 
                  <?php 
                    $start_time = date('H:i', strtotime($reservation['meeting_date']));
                    $end_time = date('H:i', strtotime($reservation['meeting_date'] . ' +1 hour'));
                    echo h($start_time . ' - ' . $end_time);
                  ?>
                </div>
              </div>

              <div class="info-item">
                <div class="info-label">
                  <i data-lucide="user"></i>
                  担当キャリアコンサルタント
                </div>
                <div class="info-value">
                  <?php echo h($reservation['trainer_name']); ?>
                </div>
              </div>

              <div class="info-item">
                <div class="info-label">
                  <i data-lucide="calendar-check"></i>
                  フィードバック提出日
                </div>
                <div class="info-value">
                  <?php echo h(date('Y年m月d日', strtotime($reservation['feedback_date']))); ?>
                </div>
              </div>
            </div>
          </div>
        </article>
      </section>

      <!-- フィードバック内容 -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="file-text"></i>
              キャリアコンサルタントからのフィードバック
            </h2>
          </div>
          <div class="card-body">
            
            <!-- セクション1：態度・傾聴 -->
            <div class="feedback-section strengths">
              <h3>
                <i data-lucide="ear"></i>
                1. 態度・傾聴（基本的姿勢）
              </h3>
              <div class="evaluation-criteria">
                <p><strong>評価項目：</strong></p>
                <ul>
                  <li>受容的・共感的な態度で受験者を迎えることができる</li>
                  <li>受験者との信頼関係を構築できる</li>
                  <li>適切な応答技法を用いることができる</li>
                </ul>
              </div>
              <div class="feedback-content">
                <p><?php echo nl2br(h($feedback_data['attitude_comment'])); ?></p>
              </div>
            </div>

            <!-- セクション2：問題把握 -->
            <div class="feedback-section improvements">
              <h3>
                <i data-lucide="search"></i>
                2. 問題把握
              </h3>
              <div class="evaluation-criteria">
                <p><strong>評価項目：</strong></p>
                <ul>
                  <li>受験者の主訴を明確にできる</li>
                  <li>受験者のキャリアに関する経験等を傾聴できる</li>
                  <li>受験者の真の課題を把握できる</li>
                </ul>
              </div>
              <div class="feedback-content">
                <p><?php echo nl2br(h($feedback_data['problem_comment'])); ?></p>
              </div>
            </div>

            <!-- セクション3：具体的展開 -->
            <div class="feedback-section goals">
              <h3>
                <i data-lucide="trending-up"></i>
                3. 具体的展開
              </h3>
              <div class="evaluation-criteria">
                <p><strong>評価項目：</strong></p>
                <ul>
                  <li>受験者の目標を明確にできる</li>
                  <li>受験者の自己理解や、仕事・職業の理解を深めることができる</li>
                  <li>受験者に対して適切な支援を行うことができる</li>
                </ul>
              </div>
              <div class="feedback-content">
                <p><?php echo nl2br(h($feedback_data['development_comment'])); ?></p>
              </div>
            </div>

            <!-- 次回へのアドバイス -->
            <div class="overall-comment">
              <h3>
                <i data-lucide="lightbulb"></i>
                次回の面談に向けたアドバイス
              </h3>
              <p><?php echo nl2br(h($feedback_data['next_advice'])); ?></p>
            </div>

          </div>
          <div class="card-footer">
            <a href="../../../mypage.php" class="btn-secondary btn-block">
              <i data-lucide="arrow-left"></i>
              マイページに戻る
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
