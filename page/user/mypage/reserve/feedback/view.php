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

// 成功・エラーメッセージ取得
$success = getSessionMessage('success');
$error = getSessionMessage('error');
$errors = getSessionMessage('errors');

// 予約詳細とフィードバック情報を取得
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.meeting_url,
        r.recording_url,
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

// フィードバックデータの解析
$has_trainer_feedback = !empty($reservation['feedback_comment']);
$feedback_data = [
    'attitude_comment' => '',
    'attitude_score_1' => 0,
    'attitude_score_2' => 0,
    'problem_comment' => '',
    'problem_score_1' => 0,
    'problem_score_2' => 0,
    'development_comment' => '',
    'development_score_1' => 0,
    'development_score_2' => 0,
    'next_advice' => '',
];

if ($has_trainer_feedback) {
    $feedback_json = json_decode($reservation['feedback_comment'], true);
    $feedback_data = [
        'attitude_comment' => $feedback_json['attitude_comment'] ?? '',
        'attitude_score_1' => $feedback_json['attitude_score_1'] ?? 0,
        'attitude_score_2' => $feedback_json['attitude_score_2'] ?? 0,
        'problem_comment' => $feedback_json['problem_comment'] ?? '',
        'problem_score_1' => $feedback_json['problem_score_1'] ?? 0,
        'problem_score_2' => $feedback_json['problem_score_2'] ?? 0,
        'development_comment' => $feedback_json['development_comment'] ?? '',
        'development_score_1' => $feedback_json['development_score_1'] ?? 0,
        'development_score_2' => $feedback_json['development_score_2'] ?? 0,
        'next_advice' => $feedback_json['next_advice'] ?? '',
    ];
}

// 自己フィードバックを取得
$stmt = $pdo->prepare("
    SELECT id, comment, created_at 
    FROM reports 
    WHERE reserve_id = ? AND user_id = ?
");
$stmt->execute([$reservation_id, $user_id]);
$self_feedback_record = $stmt->fetch(PDO::FETCH_ASSOC);

$has_self_feedback = !empty($self_feedback_record);
$self_feedback = [
    'satisfaction' => 0,
    'strengths' => '',
    'challenges' => '',
    'learnings' => '',
    'next_goals' => '',
];

if ($has_self_feedback) {
    $self_feedback_json = json_decode($self_feedback_record['comment'], true);
    $self_feedback = [
        'satisfaction' => $self_feedback_json['satisfaction'] ?? 0,
        'strengths' => $self_feedback_json['strengths'] ?? '',
        'challenges' => $self_feedback_json['challenges'] ?? '',
        'learnings' => $self_feedback_json['learnings'] ?? '',
        'next_goals' => $self_feedback_json['next_goals'] ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>フィードバック詳細 - CareerTre キャリアトレーナーズ</title>
  
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
          <span class="navbar-tagline">-キャリアトレーナーズ-</span>
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
        <p class="hero-tagline">-キャリアトレーナーズ-</p>
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

      <!-- 面談録画動画 -->
      <?php if (!empty($reservation['recording_url'])): ?>
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="video"></i>
              面談録画動画
            </h2>
            <p style="margin-top: var(--spacing-sm); color: var(--text-secondary);">
              Google Meetで録画された面談の様子を確認できます
            </p>
          </div>
          <div class="card-body">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: var(--radius-lg); padding: var(--spacing-xl); text-align: center; color: white;">
              <div style="font-size: 4rem; margin-bottom: var(--spacing-md);">
                <i data-lucide="play-circle" style="width: 80px; height: 80px;"></i>
              </div>
              <h3 style="color: white; margin-bottom: var(--spacing-sm);">面談録画動画が利用可能です</h3>
              <p style="color: rgba(255,255,255,0.9); margin-bottom: var(--spacing-lg);">
                ご自身の面談の様子を振り返り、改善点を見つけることができます
              </p>
              <a href="<?php echo h($reservation['recording_url']); ?>" target="_blank" class="btn-secondary" style="background: white; color: #667eea; border: none; padding: var(--spacing-md) var(--spacing-xl); font-weight: bold; display: inline-flex; align-items: center; gap: var(--spacing-xs);">
                <i data-lucide="external-link"></i>
                録画動画を見る
              </a>
            </div>
            <div style="margin-top: var(--spacing-md); padding: var(--spacing-md); background: #f0f9ff; border-radius: var(--radius-md); border-left: 4px solid #3b82f6;">
              <h4 style="margin: 0 0 var(--spacing-sm) 0; font-size: var(--font-size-md); color: var(--text-primary); display: flex; align-items: center; gap: var(--spacing-xs);">
                <i data-lucide="info"></i>
                録画動画の活用方法
              </h4>
              <ul style="margin: 0; padding-left: var(--spacing-lg); font-size: var(--font-size-sm); color: var(--text-secondary); line-height: 1.8;">
                <li>自分の話し方や表情、姿勢を客観的に確認できます</li>
                <li>傾聴姿勢や相槌のタイミングをチェックしましょう</li>
                <li>質問の内容や展開を振り返り、改善点を見つけましょう</li>
                <li>キャリアコンサルタントのフィードバックと照らし合わせて学習効果を高めましょう</li>
              </ul>
            </div>
          </div>
        </article>
      </section>
      <?php endif; ?>

      <!-- フィードバック内容 -->
      <?php if ($has_trainer_feedback): ?>
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
              <?php if ($feedback_data['attitude_score_1'] > 0 || $feedback_data['attitude_score_2'] > 0): ?>
              <div class="evaluation-scores" style="display: flex; flex-direction: column; gap: var(--spacing-md); margin-bottom: var(--spacing-md); padding: var(--spacing-md); background: #f8f9fa; border-radius: 8px;">
                <div class="score-item">
                  <strong style="color: #2c3e50;">チェックポイント1: 相談者が話しやすい雰囲気（表情・相槌・声のトーン）だったか</strong>
                  <div style="display: flex; align-items: center; margin-top: 0.5rem;">
                    <span style="font-size: 1.2rem; font-weight: 600; color: var(--color-primary);">スコア: <?php echo h($feedback_data['attitude_score_1']); ?> / 5</span>
                    <span style="margin-left: 1rem; color: #666;">
                      <?php 
                      $stars = str_repeat('★', $feedback_data['attitude_score_1']) . str_repeat('☆', 5 - $feedback_data['attitude_score_1']);
                      echo $stars;
                      ?>
                    </span>
                  </div>
                </div>
                <div class="score-item">
                  <strong style="color: #2c3e50;">チェックポイント2: 感情への共感を示し、信頼関係（ラポール）を築けていたか</strong>
                  <div style="display: flex; align-items: center; margin-top: 0.5rem;">
                    <span style="font-size: 1.2rem; font-weight: 600; color: var(--color-primary);">スコア: <?php echo h($feedback_data['attitude_score_2']); ?> / 5</span>
                    <span style="margin-left: 1rem; color: #666;">
                      <?php 
                      $stars = str_repeat('★', $feedback_data['attitude_score_2']) . str_repeat('☆', 5 - $feedback_data['attitude_score_2']);
                      echo $stars;
                      ?>
                    </span>
                  </div>
                </div>
              </div>
              <?php endif; ?>
              <div class="feedback-content">
                <strong>具体的なフィードバック:</strong>
                <p><?php echo nl2br(h($feedback_data['attitude_comment'])); ?></p>
              </div>
            </div>

            <!-- セクション2：問題把握 -->
            <div class="feedback-section improvements">
              <h3>
                <i data-lucide="search"></i>
                2. 問題把握
              </h3>
              <?php if ($feedback_data['problem_score_1'] > 0 || $feedback_data['problem_score_2'] > 0): ?>
              <div class="evaluation-scores" style="display: flex; flex-direction: column; gap: var(--spacing-md); margin-bottom: var(--spacing-md); padding: var(--spacing-md); background: #f8f9fa; border-radius: 8px;">
                <div class="score-item">
                  <strong style="color: #2c3e50;">チェックポイント1: 相談者が一番言いたかったこと（主訴）をつかめていたか</strong>
                  <div style="display: flex; align-items: center; margin-top: 0.5rem;">
                    <span style="font-size: 1.2rem; font-weight: 600; color: var(--color-primary);">スコア: <?php echo h($feedback_data['problem_score_1']); ?> / 5</span>
                    <span style="margin-left: 1rem; color: #666;">
                      <?php 
                      $stars = str_repeat('★', $feedback_data['problem_score_1']) . str_repeat('☆', 5 - $feedback_data['problem_score_1']);
                      echo $stars;
                      ?>
                    </span>
                  </div>
                </div>
                <div class="score-item">
                  <strong style="color: #2c3e50;">チェックポイント2: CLの自己理解不足や仕事理解不足など、客観的な課題を見つけたか</strong>
                  <div style="display: flex; align-items: center; margin-top: 0.5rem;">
                    <span style="font-size: 1.2rem; font-weight: 600; color: var(--color-primary);">スコア: <?php echo h($feedback_data['problem_score_2']); ?> / 5</span>
                    <span style="margin-left: 1rem; color: #666;">
                      <?php 
                      $stars = str_repeat('★', $feedback_data['problem_score_2']) . str_repeat('☆', 5 - $feedback_data['problem_score_2']);
                      echo $stars;
                      ?>
                    </span>
                  </div>
                </div>
              </div>
              <?php endif; ?>
              <div class="feedback-content">
                <strong>具体的なフィードバック:</strong>
                <p><?php echo nl2br(h($feedback_data['problem_comment'])); ?></p>
              </div>
            </div>

            <!-- セクション3：具体的展開 -->
            <div class="feedback-section goals">
              <h3>
                <i data-lucide="trending-up"></i>
                3. 具体的展開
              </h3>
              <?php if ($feedback_data['development_score_1'] > 0 || $feedback_data['development_score_2'] > 0): ?>
              <div class="evaluation-scores" style="display: flex; flex-direction: column; gap: var(--spacing-md); margin-bottom: var(--spacing-md); padding: var(--spacing-md); background: #f8f9fa; border-radius: 8px;">
                <div class="score-item">
                  <strong style="color: #2c3e50;">チェックポイント1: 相談者の「気づき」を促す問いかけや要約があったか</strong>
                  <div style="display: flex; align-items: center; margin-top: 0.5rem;">
                    <span style="font-size: 1.2rem; font-weight: 600; color: var(--color-primary);">スコア: <?php echo h($feedback_data['development_score_1']); ?> / 5</span>
                    <span style="margin-left: 1rem; color: #666;">
                      <?php 
                      $stars = str_repeat('★', $feedback_data['development_score_1']) . str_repeat('☆', 5 - $feedback_data['development_score_1']);
                      echo $stars;
                      ?>
                    </span>
                  </div>
                </div>
                <div class="score-item">
                  <strong style="color: #2c3e50;">チェックポイント2: 目標共有ができ、次の一歩に向けた動機づけができたか</strong>
                  <div style="display: flex; align-items: center; margin-top: 0.5rem;">
                    <span style="font-size: 1.2rem; font-weight: 600; color: var(--color-primary);">スコア: <?php echo h($feedback_data['development_score_2']); ?> / 5</span>
                    <span style="margin-left: 1rem; color: #666;">
                      <?php 
                      $stars = str_repeat('★', $feedback_data['development_score_2']) . str_repeat('☆', 5 - $feedback_data['development_score_2']);
                      echo $stars;
                      ?>
                    </span>
                  </div>
                </div>
              </div>
              <?php endif; ?>
              <div class="feedback-content">
                <strong>具体的なフィードバック:</strong>
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
        </article>
      </section>
      <?php endif; ?>

      <!-- 自己フィードバック -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="edit"></i>
              あなたの自己フィードバック
            </h2>
            <p style="margin-top: var(--spacing-sm); color: var(--text-secondary);">
              面談を振り返り、気づきや学びを記録しましょう
            </p>
          </div>
          <div class="card-body">
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

            <?php if ($has_self_feedback): ?>
              <!-- 保存済み自己フィードバック表示 -->
              <div class="feedback-section" style="background: #f0fdf4; border-left: 4px solid #10b981; padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-md);">
                <h4 style="margin: 0 0 var(--spacing-sm) 0; color: #059669;">満足度</h4>
                <div style="margin-bottom: var(--spacing-md);">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span style="color: <?php echo $i <= $self_feedback['satisfaction'] ? '#f59e0b' : '#d1d5db'; ?>; font-size: 1.5rem;">★</span>
                  <?php endfor; ?>
                </div>

                <h4 style="margin: var(--spacing-md) 0 var(--spacing-sm) 0; color: #059669;">良かった点</h4>
                <div style="white-space: pre-line; line-height: 1.8; margin-bottom: var(--spacing-md);">
                  <?php echo nl2br(h($self_feedback['strengths'])); ?>
                </div>

                <h4 style="margin: var(--spacing-md) 0 var(--spacing-sm) 0; color: #059669;">改善が必要な点</h4>
                <div style="white-space: pre-line; line-height: 1.8; margin-bottom: var(--spacing-md);">
                  <?php echo nl2br(h($self_feedback['challenges'])); ?>
                </div>

                <h4 style="margin: var(--spacing-md) 0 var(--spacing-sm) 0; color: #059669;">気づき・学び</h4>
                <div style="white-space: pre-line; line-height: 1.8; margin-bottom: var(--spacing-md);">
                  <?php echo nl2br(h($self_feedback['learnings'])); ?>
                </div>

                <h4 style="margin: var(--spacing-md) 0 var(--spacing-sm) 0; color: #059669;">次回の目標</h4>
                <div style="white-space: pre-line; line-height: 1.8;">
                  <?php echo nl2br(h($self_feedback['next_goals'])); ?>
                </div>
              </div>

              <button onclick="document.getElementById('editForm').style.display='block'; this.style.display='none';" class="btn-secondary">
                <i data-lucide="edit"></i>
                編集する
              </button>

              <!-- 編集フォーム（非表示） -->
              <form id="editForm" action="../../../../../controller/user/self_feedback_save.php" method="POST" style="display: none; margin-top: var(--spacing-md);">
                <input type="hidden" name="reserve_id" value="<?php echo h($reservation_id); ?>">
                
                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label>満足度 <span class="required">*</span></label>
                  <div style="display: flex; gap: var(--spacing-xs);">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label style="cursor: pointer;">
                        <input type="radio" name="satisfaction" value="<?php echo $i; ?>" <?php echo $self_feedback['satisfaction'] == $i ? 'checked' : ''; ?> required>
                        <?php echo $i; ?>★
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label for="strengths">良かった点 <span class="required">*</span></label>
                  <textarea id="strengths" name="strengths" rows="4" required style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--color-border); border-radius: var(--radius-md);"><?php echo h($self_feedback['strengths']); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label for="challenges">改善が必要な点 <span class="required">*</span></label>
                  <textarea id="challenges" name="challenges" rows="4" required style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--color-border); border-radius: var(--radius-md);"><?php echo h($self_feedback['challenges']); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label for="learnings">気づき・学び <span class="required">*</span></label>
                  <textarea id="learnings" name="learnings" rows="4" required style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--color-border); border-radius: var(--radius-md);"><?php echo h($self_feedback['learnings']); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label for="next_goals">次回の目標 <span class="required">*</span></label>
                  <textarea id="next_goals" name="next_goals" rows="4" required style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--color-border); border-radius: var(--radius-md);"><?php echo h($self_feedback['next_goals']); ?></textarea>
                </div>

                <div style="display: flex; gap: var(--spacing-sm);">
                  <button type="submit" class="btn-primary" style="flex: 1;">
                    <i data-lucide="save"></i>
                    更新する
                  </button>
                  <button type="button" onclick="this.closest('form').style.display='none'; document.querySelector('.btn-secondary').style.display='inline-flex';" class="btn-secondary" style="flex: 1;">
                    キャンセル
                  </button>
                </div>
              </form>
            <?php else: ?>
              <!-- 自己フィードバック入力フォーム -->
              <form action="../../../../../controller/user/self_feedback_save.php" method="POST">
                <input type="hidden" name="reserve_id" value="<?php echo h($reservation_id); ?>">
                
                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label>満足度 <span class="required">*</span></label>
                  <p style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--spacing-sm);">
                    この練習セッション全体への満足度を教えてください
                  </p>
                  <div style="display: flex; gap: var(--spacing-md);">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label style="cursor: pointer; display: flex; align-items: center; gap: 4px;">
                        <input type="radio" name="satisfaction" value="<?php echo $i; ?>" required>
                        <span><?php echo $i; ?>★</span>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label for="strengths">良かった点 <span class="required">*</span></label>
                  <p style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--spacing-sm);">
                    できたこと、発揮できた強みなど
                  </p>
                  <textarea 
                    id="strengths" 
                    name="strengths" 
                    rows="4" 
                    placeholder="例：相手の話を最後まで聞く傾聴姿勢を意識できた"
                    required
                    style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--color-border); border-radius: var(--radius-md); font-size: var(--font-size-sm); font-family: inherit;"
                  ></textarea>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label for="challenges">改善が必要な点 <span class="required">*</span></label>
                  <p style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--spacing-sm);">
                    難しかったこと、課題と感じたこと
                  </p>
                  <textarea 
                    id="challenges" 
                    name="challenges" 
                    rows="4" 
                    placeholder="例：開かれた質問の組み立てが難しかった"
                    required
                    style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--color-border); border-radius: var(--radius-md); font-size: var(--font-size-sm); font-family: inherit;"
                  ></textarea>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label for="learnings">気づき・学び <span class="required">*</span></label>
                  <p style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--spacing-sm);">
                    この練習を通じて学んだこと、新しい発見
                  </p>
                  <textarea 
                    id="learnings" 
                    name="learnings" 
                    rows="4" 
                    placeholder="例：相手の話を遮らずに聞くことの重要性を実感した"
                    required
                    style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--color-border); border-radius: var(--radius-md); font-size: var(--font-size-sm); font-family: inherit;"
                  ></textarea>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                  <label for="next_goals">次回の目標 <span class="required">*</span></label>
                  <p style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--spacing-sm);">
                    次回に向けて取り組みたいこと
                  </p>
                  <textarea 
                    id="next_goals" 
                    name="next_goals" 
                    rows="4" 
                    placeholder="例：質問力を向上させるため、質問のバリエーションを増やす"
                    required
                    style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--color-border); border-radius: var(--radius-md); font-size: var(--font-size-sm); font-family: inherit;"
                  ></textarea>
                </div>

                <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-lg);">
                  <button type="submit" class="btn-primary" style="flex: 1;">
                    <i data-lucide="save"></i>
                    自己フィードバックを保存
                  </button>
                  <a href="../../../mypage.php" class="btn-secondary" style="flex: 1; text-align: center;">
                    <i data-lucide="arrow-left"></i>
                    後で記入する
                  </a>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </article>
      </section>

      <!-- 戻るボタン -->
      <section class="content-section fade-in">
        <a href="../../../mypage.php" class="btn-secondary btn-block">
          <i data-lucide="arrow-left"></i>
          マイページに戻る
        </a>
      </section>

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
  </script>
</body>
</html>
