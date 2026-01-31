<?php
// 管理者専用レポート詳細ページ
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/database.php';
requireLogin('trainer');

// 現在のユーザー情報取得
$current_user = getCurrentUser();

// 管理者チェック（naoko.s1110@gmail.com のみ）
if ($current_user['email'] !== 'naoko.s1110@gmail.com') {
    setSessionMessage('error', '管理者権限がありません');
    redirect('/gs_code/gga/page/admin/dashboard.php');
}

// 予約IDの取得
$reserve_id = $_GET['id'] ?? null;

if (!$reserve_id) {
    setSessionMessage('error', '予約IDが指定されていません');
    redirect('/gs_code/gga/page/admin/dashboard.php');
}

// データベース接続
$pdo = getDBConnection();

// 予約情報とトレーナーフィードバックを取得
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.status,
        r.recording_url,
        u.id as user_id,
        u.name as user_name,
        u.email as user_email,
        t.name as trainer_name,
        t.email as trainer_email,
        p.persona_name,
        p.age as persona_age,
        p.family_structure,
        p.job as persona_job,
        p.situation as persona_situation,
        f.id as feedback_id,
        f.comment as trainer_feedback,
        f.created_at as feedback_date
    FROM reserves r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN personas p ON r.persona_id = p.id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    WHERE r.id = :reserve_id
");
$stmt->execute(['reserve_id' => $reserve_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    setSessionMessage('error', '予約情報が見つかりません');
    redirect('/gs_code/gga/page/admin/dashboard.php');
}

// トレーナーフィードバックをデコード
$trainer_feedback_data = [
    'attitude_comment' => '',
    'problem_comment' => '',
    'development_comment' => '',
    'next_advice' => ''
];

if ($reservation['trainer_feedback']) {
    $feedback_json = json_decode($reservation['trainer_feedback'], true);
    if ($feedback_json) {
        $trainer_feedback_data = [
            'attitude_comment' => $feedback_json['attitude_comment'] ?? '',
            'problem_comment' => $feedback_json['problem_comment'] ?? '',
            'development_comment' => $feedback_json['development_comment'] ?? '',
            'next_advice' => $feedback_json['next_advice'] ?? ''
        ];
    }
}

// 受験者の自己フィードバックを取得（reportsテーブルから）
$stmt = $pdo->prepare("
    SELECT 
        id,
        comment,
        created_at
    FROM reports
    WHERE reserve_id = :reserve_id
    LIMIT 1
");
$stmt->execute(['reserve_id' => $reserve_id]);
$self_feedback_raw = $stmt->fetch(PDO::FETCH_ASSOC);

// 自己フィードバックをデコード
$self_feedback = null;
if ($self_feedback_raw && !empty($self_feedback_raw['comment'])) {
    $comment_json = json_decode($self_feedback_raw['comment'], true);
    if ($comment_json) {
        $self_feedback = [
            'id' => $self_feedback_raw['id'],
            'satisfaction' => $comment_json['satisfaction'] ?? 0,
            'strengths' => $comment_json['strengths'] ?? '',
            'challenges' => $comment_json['challenges'] ?? '',
            'learnings' => $comment_json['learnings'] ?? '',
            'next_goals' => $comment_json['next_goals'] ?? '',
            'created_at' => $self_feedback_raw['created_at']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>レポート詳細 - 管理者</title>
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  <link rel="stylesheet" href="../../assets/css/variables.css?v=2.1">
  <link rel="stylesheet" href="../../assets/css/custom.css?v=2.1">
  
  <style>
    body {
      background: linear-gradient(135deg, #FFF8E1 0%, #FFFFFF 100%);
      min-height: 100vh;
    }
    
    .admin-header {
      background: linear-gradient(135deg, #E65100, #FF6F00);
      padding: 25px 0;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 40px;
    }
    
    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }
    
    .logo-section h1 {
      font-size: 1.8rem;
      font-weight: 900;
      color: white;
      margin: 0;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px 40px;
    }
    
    .info-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    
    .info-item {
      padding: 15px;
      background: #FFF8E1;
      border-radius: 8px;
    }
    
    .info-item label {
      font-size: 0.85rem;
      color: #666;
      font-weight: 600;
      display: block;
      margin-bottom: 5px;
    }
    
    .info-item .value {
      font-size: 1rem;
      color: #333;
      font-weight: 500;
    }
    
    .section-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #333;
      margin: 0 0 20px 0;
      padding-bottom: 15px;
      border-bottom: 3px solid #FFE0B2;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .feedback-item {
      margin-bottom: 25px;
    }
    
    .feedback-item h3 {
      font-size: 1.1rem;
      color: #FF9800;
      margin: 0 0 10px 0;
      font-weight: 600;
    }
    
    .feedback-item .content {
      background: #F5F5F5;
      padding: 20px;
      border-radius: 8px;
      line-height: 1.8;
      white-space: pre-wrap;
      color: #333;
    }
    
    .no-feedback {
      text-align: center;
      padding: 40px;
      color: #999;
      background: #FAFAFA;
      border-radius: 8px;
    }
    
    .btn-back {
      background: #FF9800;
      color: white;
      padding: 10px 25px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-back:hover {
      background: #F57C00;
    }
    
    .status-badge {
      display: inline-block;
      padding: 6px 16px;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
    }
    
    .status-completed {
      background: #E8F5E9;
      color: #2E7D32;
    }
    
    .status-pending {
      background: #FFF3E0;
      color: #E65100;
    }
    
    .rating-stars {
      display: flex;
      gap: 5px;
      margin-top: 10px;
    }
    
    .star {
      font-size: 1.5rem;
      color: #FFB300;
    }
  </style>
</head>
<body>
  <header class="admin-header">
    <div class="header-content">
      <div class="logo-section">
        <h1>📋 レポート詳細 - 管理者</h1>
      </div>
      <a href="dashboard.php" class="btn-back">
        <i data-lucide="arrow-left"></i>
        ダッシュボードに戻る
      </a>
    </div>
  </header>

  <div class="container">
    <!-- セッション情報 -->
    <div class="info-card">
      <h2 style="margin: 0 0 20px 0; font-size: 1.3rem; color: #333;">セッション情報</h2>
      <div class="info-grid">
        <div class="info-item">
          <label>予約ID</label>
          <div class="value"><?php echo h($reservation['id']); ?></div>
        </div>
        <div class="info-item">
          <label>実施日時</label>
          <div class="value"><?php echo h(date('Y年m月d日 H:i', strtotime($reservation['meeting_date']))); ?></div>
        </div>
        <div class="info-item">
          <label>受験者</label>
          <div class="value"><?php echo h($reservation['user_name']); ?></div>
          <small style="color: #666;"><?php echo h($reservation['user_email']); ?></small>
        </div>
        <div class="info-item">
          <label>トレーナー</label>
          <div class="value"><?php echo h($reservation['trainer_name']); ?></div>
          <small style="color: #666;"><?php echo h($reservation['trainer_email']); ?></small>
        </div>
        <div class="info-item">
          <label>ペルソナ</label>
          <div class="value"><?php echo h($reservation['persona_name'] ?: '未割当'); ?></div>
        </div>
        <div class="info-item">
          <label>ステータス</label>
          <div class="value">
            <span class="status-badge status-completed">実施完了</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 録画URL入力 -->
    <div class="section-card">
      <h2 class="section-title">
        <i data-lucide="video"></i>
        録画URL管理（管理者専用）
      </h2>
      <form method="POST" action="update_recording_url.php" style="margin-top: 20px;">
        <input type="hidden" name="reserve_id" value="<?php echo h($reserve_id); ?>">
        <div class="info-item">
          <label for="recording_url" style="font-size: 1rem; margin-bottom: 10px; display: block;">Google Meet録画URL</label>
          <input 
            type="url" 
            id="recording_url" 
            name="recording_url" 
            value="<?php echo h($reservation['recording_url'] ?? ''); ?>"
            placeholder="https://drive.google.com/file/d/..."
            style="width: 100%; padding: 12px; border: 2px solid #FFE0B2; border-radius: 8px; font-size: 1rem;"
          >
          <small style="color: #666; display: block; margin-top: 8px;">このURLは受験者のフィードバックページに表示されます</small>
        </div>
        <div style="text-align: right; margin-top: 15px;">
          <button 
            type="submit" 
            style="background: #FF9800; color: white; padding: 12px 30px; border: none; border-radius: 25px; font-weight: 600; cursor: pointer; font-size: 1rem;"
          >
            <i data-lucide="save" style="width: 18px; height: 18px; vertical-align: middle; margin-right: 5px;"></i>
            録画URLを保存
          </button>
        </div>
      </form>
    </div>

    <!-- トレーナーフィードバック -->
    <div class="section-card">
      <h2 class="section-title">
        <i data-lucide="user-check"></i>
        トレーナーフィードバック
      </h2>
      <?php if ($reservation['feedback_id']): ?>
        <div style="text-align: right; margin-bottom: 15px; color: #666;">
          <small>提出日: <?php echo h(date('Y年m月d日', strtotime($reservation['feedback_date']))); ?></small>
        </div>
        
        <div class="feedback-item">
          <h3>1. 態度・姿勢について</h3>
          <div class="content"><?php echo h($trainer_feedback_data['attitude_comment']) ?: '記載なし'; ?></div>
        </div>
        
        <div class="feedback-item">
          <h3>2. 問題把握について</h3>
          <div class="content"><?php echo h($trainer_feedback_data['problem_comment']) ?: '記載なし'; ?></div>
        </div>
        
        <div class="feedback-item">
          <h3>3. 具体的展開について</h3>
          <div class="content"><?php echo h($trainer_feedback_data['development_comment']) ?: '記載なし'; ?></div>
        </div>
        
        <div class="feedback-item">
          <h3>4. 次回に向けてのアドバイス</h3>
          <div class="content"><?php echo h($trainer_feedback_data['next_advice']) ?: '記載なし'; ?></div>
        </div>
      <?php else: ?>
        <div class="no-feedback">
          <p>トレーナーフィードバックは未提出です</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- 受験者自己フィードバック -->
    <div class="section-card">
      <h2 class="section-title">
        <i data-lucide="message-square"></i>
        受験者自己フィードバック
      </h2>
      <?php if ($self_feedback): ?>
        <div style="text-align: right; margin-bottom: 15px; color: #666;">
          <small>提出日: <?php echo h(date('Y年m月d日', strtotime($self_feedback['created_at']))); ?></small>
        </div>
        
        <div class="feedback-item">
          <h3>満足度</h3>
          <div class="rating-stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star"><?php echo $i <= $self_feedback['satisfaction'] ? '★' : '☆'; ?></span>
            <?php endfor; ?>
            <span style="margin-left: 10px; color: #666;"><?php echo h($self_feedback['satisfaction']); ?> / 5</span>
          </div>
        </div>
        
        <div class="feedback-item">
          <h3>良かった点・できたこと</h3>
          <div class="content"><?php echo h($self_feedback['strengths']) ?: '記載なし'; ?></div>
        </div>
        
        <div class="feedback-item">
          <h3>課題・改善したいこと</h3>
          <div class="content"><?php echo h($self_feedback['challenges']) ?: '記載なし'; ?></div>
        </div>
        
        <div class="feedback-item">
          <h3>学び・気づき</h3>
          <div class="content"><?php echo h($self_feedback['learnings']) ?: '記載なし'; ?></div>
        </div>
        
        <div class="feedback-item">
          <h3>次回の目標</h3>
          <div class="content"><?php echo h($self_feedback['next_goals']) ?: '記載なし'; ?></div>
        </div>
      <?php else: ?>
        <div class="no-feedback">
          <p>受験者の自己フィードバックは未提出です</p>
        </div>
      <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 40px;">
      <a href="dashboard.php" class="btn-back">
        <i data-lucide="arrow-left"></i>
        ダッシュボードに戻る
      </a>
    </div>
  </div>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>
</body>
</html>
