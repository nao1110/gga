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
$pdo = getDBConnection();

// 予約データ取得
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.status,
        r.meeting_url,
        r.created_at,
        t.name as trainer_name,
        t.email as trainer_email,
        p.id as persona_id,
        p.persona_name,
        p.age as persona_age,
        p.family_structure as persona_family,
        p.job as persona_job,
        p.situation as persona_situation,
        f.id as feedback_id,
        f.created_at as feedback_date
    FROM reserves r
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN personas p ON r.persona_id = p.id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    WHERE r.user_id = ?
    ORDER BY r.meeting_date DESC
");
$stmt->execute([$user_id]);
$all_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 状態別に分類
$pending_requests = [];
$confirmed_sessions = [];
$completed_sessions = [];

foreach ($all_reservations as $reservation) {
    if ($reservation['status'] === 'pending') {
        $pending_requests[] = $reservation;
    } elseif ($reservation['status'] === 'confirmed') {
        $confirmed_sessions[] = $reservation;
    } elseif ($reservation['status'] === 'completed') {
        $completed_sessions[] = $reservation;
    }
}

// 完了セッションの総数
$total_completed_count = count($completed_sessions);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>マイページ - キャリアトレーナーズ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../assets/css/variables.css?v=2.1">
  <link rel="stylesheet" href="../../assets/css/custom.css?v=2.1">
  
  <style>
    body {
      background: linear-gradient(135deg, #FFF8E1 0%, #FFFFFF 100%);
      min-height: 100vh;
    }
    
    .user-header {
      background: white;
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
      font-size: 2rem;
      font-weight: 900;
      background: linear-gradient(135deg, #FF9800, #F57C00);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin: 0;
    }
    
    .logo-section p {
      color: #666;
      font-size: 0.9rem;
      margin: 5px 0 0 0;
    }
    
    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
    }
    
    .user-badge {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: white;
      border-radius: 25px;
      font-weight: 600;
    }
    
    .btn-logout {
      background: #9E9E9E;
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }
    
    .btn-logout:hover {
      background: #757575;
    }
    
    .step-container {
      max-width: 1200px;
      margin: 0 auto 40px;
      padding: 0 20px;
    }
    
    .step-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .step-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    
    .step-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
      padding-bottom: 20px;
      border-bottom: 3px solid #FFE0B2;
    }
    
    .step-number {
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      font-weight: 900;
      flex-shrink: 0;
    }
    
    .step-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #333;
      margin: 0;
    }
    
    .count-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 30px;
      height: 30px;
      padding: 0 10px;
      background: #FF5722;
      color: white;
      border-radius: 15px;
      font-size: 0.9rem;
      font-weight: 700;
      margin-left: 10px;
    }
    
    .count-badge.completed {
      background: #4CAF50;
    }
    
    .count-badge.none {
      background: #9E9E9E;
    }
    
    .reservation-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    
    .reservation-card {
      background: #FFF8E1;
      border: 2px solid #FFE0B2;
      border-radius: 12px;
      padding: 30px;
      transition: all 0.3s;
    }
    
    .reservation-card:hover {
      border-color: #FF9800;
      box-shadow: 0 4px 12px rgba(255, 152, 0, 0.2);
    }
    
    .card-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid #FFE0B2;
    }
    
    .info-box {
      background: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 15px;
    }
    
    .info-label {
      font-size: 0.85rem;
      color: #666;
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .info-value {
      font-size: 1.1rem;
      color: #333;
      font-weight: 600;
    }
    
    .btn-action {
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
      border: none;
      cursor: pointer;
    }
    
    .btn-action:hover {
      background: linear-gradient(135deg, #F57C00, #E65100);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
    }
    
    .btn-view {
      background: white;
      color: #FF9800;
      border: 2px solid #FF9800;
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s;
    }
    
    .btn-view:hover {
      background: #FF9800;
      color: white;
    }
    
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }
    
    .alert-success {
      background: #E8F5E9;
      border-left: 4px solid #4CAF50;
      color: #2E7D32;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .meet-url {
      background: #E3F2FD;
      padding: 12px;
      border-radius: 8px;
      margin: 15px 0;
    }
    
    .meet-url a {
      color: #1976D2;
      font-weight: 600;
      text-decoration: none;
      word-break: break-all;
    }
    
    .meet-url a:hover {
      text-decoration: underline;
    }
    
    .warning-message {
      background: #FFF3E0;
      border-left: 4px solid #FF9800;
      color: #E65100;
      padding: 12px;
      border-radius: 8px;
      font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
      .reservation-grid {
        grid-template-columns: 1fr;
      }
      
      .step-header {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>
  <!-- ヘッダー -->
  <header class="user-header">
    <div class="header-content">
      <div class="logo-section">
        <h1>キャリアトレーナーズ</h1>
        <p>Career Trainers - 受験者マイページ</p>
      </div>
      <div class="header-actions">
        <div class="user-badge">
          <i data-lucide="user"></i>
          <?php echo h($user_name); ?>
        </div>
        <a href="../../controller/logout.php" class="btn-logout">
          <i data-lucide="log-out"></i>
          ログアウト
        </a>
      </div>
    </div>
  </header>

  <?php if ($success): ?>
  <div class="step-container">
    <div class="alert-success">
      <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
      <?= h($success) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- STEP 1: 新しい練習の予約リクエスト -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">1</div>
        <h2 class="step-title">新しい練習の予約リクエスト</h2>
      </div>
      
      <div style="text-align: center; padding: 20px 0;">
        <p style="font-size: 1.1rem; color: #666; margin-bottom: 25px;">
          実技試験の練習セッションを予約しましょう
        </p>
        <a href="mypage/reserve/new.php" class="btn-action" style="font-size: 1.1rem; padding: 15px 40px;">
          <i data-lucide="calendar-plus"></i>
          練習を予約リクエストする
        </a>
      </div>
    </div>
  </div>

  <!-- STEP 2: リクエスト状況確認 -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">2</div>
        <h2 class="step-title">
          リクエスト状況確認
          <span class="count-badge <?php echo count($pending_requests) > 0 ? '' : 'none'; ?>">
            <?php echo count($pending_requests); ?>
          </span>
        </h2>
      </div>
      
      <?php if (!empty($pending_requests)): ?>
      <div class="reservation-grid">
        <?php foreach ($pending_requests as $request): ?>
        <div class="reservation-card">
          <div class="card-row">
            <div>
              <div class="info-label">予約ID</div>
              <div class="info-value">#<?php echo h($request['id']); ?></div>
            </div>
            <span class="count-badge">承認待ち</span>
          </div>
          
          <div class="info-box">
            <div class="info-label">日付：</div>
            <div class="info-value">
              <?php echo h(date('Y年m月d日', strtotime($request['meeting_date']))) . '（' . getJapaneseWeekday($request['meeting_date']) . '）'; ?>
            </div>
          </div>
          
          <div class="info-box">
            <div class="info-label">時間：</div>
            <div class="info-value">
              <?php echo h(date('H:i', strtotime($request['meeting_date']))); ?> 〜 (1時間)
            </div>
          </div>
          
          <div class="info-box">
            <div class="info-label">申請日</div>
            <div class="info-value"><?php echo h(date('Y-m-d', strtotime($request['created_at']))); ?></div>
          </div>
          
          <p style="color: #666; font-size: 0.9rem; margin-top: 15px;">
            <i data-lucide="info" style="width: 16px; height: 16px; display: inline;"></i>
            キャリアコンサルタントの承認をお待ちください
          </p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <i data-lucide="calendar-x" style="width: 60px; height: 60px; margin-bottom: 15px;"></i>
        <p>承認待ちのリクエストはありません</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- STEP 3: 今後の予定（確定済み） -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">3</div>
        <h2 class="step-title">
          今後の予定（確定済み）
          <span class="count-badge <?php echo count($confirmed_sessions) > 0 ? 'completed' : 'none'; ?>">
            <?php echo count($confirmed_sessions); ?>
          </span>
        </h2>
      </div>
      
      <?php if (!empty($confirmed_sessions)): ?>
      <div class="reservation-grid">
        <?php foreach ($confirmed_sessions as $session): ?>
        <div class="reservation-card">
          <div class="card-row">
            <div>
              <div class="info-label">予約ID</div>
              <div class="info-value">#<?php echo h($session['id']); ?></div>
            </div>
            <span class="count-badge completed">確定</span>
          </div>
          
          <div class="info-box">
            <div class="info-label">担当コンサルタント</div>
            <div class="info-value"><?php echo h($session['trainer_name']); ?></div>
          </div>
          
          <div class="info-box">
            <div class="info-label">日付：</div>
            <div class="info-value">
              <?php echo h(date('Y年m月d日', strtotime($session['meeting_date']))) . '（' . getJapaneseWeekday($session['meeting_date']) . '）'; ?>
            </div>
          </div>
          
          <div class="info-box">
            <div class="info-label">時間：</div>
            <div class="info-value">
              <?php echo h(date('H:i', strtotime($session['meeting_date']))); ?> 〜 (1時間)
            </div>
          </div>
          
          <?php if ($session['persona_id']): ?>
          <div class="info-box">
            <div class="info-label">ペルソナ</div>
            <div class="info-value"><?php echo h($session['persona_name']); ?></div>
          </div>
          <?php endif; ?>
          
          <?php if ($session['meeting_url']): ?>
          <div class="meet-url">
            <div class="info-label">Google Meet URL</div>
            <a href="<?php echo h($session['meeting_url']); ?>" target="_blank">
              <?php echo h($session['meeting_url']); ?>
            </a>
          </div>
          <?php else: ?>
          <div class="warning-message">
            <i data-lucide="alert-circle" style="width: 16px; height: 16px; display: inline;"></i>
            Google MeetのURLが未設定です
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <i data-lucide="calendar" style="width: 60px; height: 60px; margin-bottom: 15px;"></i>
        <p>確定済みの予定はありません</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- STEP 4: 完了セッション・フィードバック確認 -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">4</div>
        <h2 class="step-title">
          完了セッション・フィードバック確認
          <span class="count-badge completed">
            合計 <?php echo $total_completed_count; ?> 回
          </span>
        </h2>
      </div>
      
      <?php if (!empty($completed_sessions)): ?>
      <div class="reservation-grid">
        <?php foreach ($completed_sessions as $session): ?>
        <div class="reservation-card">
          <div class="card-row">
            <div>
              <div class="info-label">予約ID</div>
              <div class="info-value">#<?php echo h($session['id']); ?></div>
            </div>
            <span class="count-badge completed">完了</span>
          </div>
          
          <div class="info-box">
            <div class="info-label">担当コンサルタント</div>
            <div class="info-value"><?php echo h($session['trainer_name']); ?></div>
          </div>
          
          <div class="info-box">
            <div class="info-label">実施日</div>
            <div class="info-value">
              <?php echo h(date('Y年m月d日', strtotime($session['meeting_date']))) . '（' . getJapaneseWeekday($session['meeting_date']) . '）'; ?>
            </div>
          </div>
          
          <?php if ($session['persona_id']): ?>
          <div class="info-box">
            <div class="info-label">ペルソナ</div>
            <div class="info-value"><?php echo h($session['persona_name']); ?></div>
          </div>
          <?php endif; ?>
          
          <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="mypage/reserve/feedback/view.php?id=<?php echo h($session['id']); ?>" class="btn-view">
              <i data-lucide="file-text"></i>
              フィードバック表示
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <i data-lucide="clipboard" style="width: 60px; height: 60px; margin-bottom: 15px;"></i>
        <p>完了したセッションはまだありません</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>
</body>
</html>
