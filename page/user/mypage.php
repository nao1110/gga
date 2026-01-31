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
        t.email as trainer_email,
        p.id as persona_id,
        p.persona_name,
        p.age,
        p.family_structure,
        p.job,
        p.theme,
        p.situation,
        f.comment as feedback_comment,
        f.id as feedback_id,
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

// 確定したセッション（confirmedでフィードバックなし）
$confirmed_sessions = array_filter($all_reservations, function($r) {
    return $r['status'] === 'confirmed' && $r['feedback_comment'] === null;
});

// 承認待ちセッション（pending）
$pending_sessions = array_filter($all_reservations, function($r) {
    return $r['status'] === 'pending';
});

// 予約総数をチェック（最大3回まで）
$total_reservations = count($all_reservations);
$max_reservations = 3;
$can_reserve = $total_reservations < $max_reservations;

// 完了数・進捗計算
$completed_count = count($completed_sessions);
$total_count = 3; // 固定：3回の練習が必要
$remaining_count = $total_count - $completed_count;
$progress_percentage = ($completed_count / $total_count) * 100;

// 3回分の練習セットデータを構築
$practice_set = [];
$completed_index = 0;
for ($i = 1; $i <= 3; $i++) {
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
      padding: 15px 30px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
      border: none;
      cursor: pointer;
      font-size: 1.1rem;
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
    
    .persona-box {
      background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
      padding: 15px;
      border-radius: 8px;
      margin: 15px 0;
      border-left: 4px solid #4CAF50;
    }
    
    .persona-title {
      font-weight: 700;
      color: #2E7D32;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .persona-details {
      color: #333;
      font-size: 0.95rem;
      line-height: 1.7;
    }
    
    .info-section {
      background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
      border-left: 5px solid #4CAF50;
      padding: 20px;
      border-radius: 10px;
      margin-top: 20px;
    }
    
    .info-section h4 {
      color: #2E7D32;
      font-size: 1rem;
      font-weight: 700;
      margin: 0 0 15px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .info-section ul {
      margin: 0;
      padding-left: 20px;
      color: #333;
      line-height: 1.8;
      font-size: 0.95rem;
    }
    
    .info-section ul li {
      margin-bottom: 8px;
    }
    
    .total-time {
      background: #2E7D32;
      color: white;
      padding: 12px;
      border-radius: 8px;
      margin-top: 15px;
      text-align: center;
      font-weight: 700;
      font-size: 1.05rem;
    }
    
    .exam-info {
      background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
      border-left: 5px solid #FF9800;
      padding: 15px;
      border-radius: 10px;
      margin-top: 15px;
    }
    
    .exam-info p {
      margin: 0;
      color: #E65100;
      font-weight: 600;
      font-size: 0.95rem;
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

  <div class="step-container">

  <div class="step-container">
    <?php if ($success): ?>
      <div class="alert-success">
        <i data-lucide="check-circle"></i>
        <?= h($success) ?>
      </div>
    <?php endif; ?>

    <!-- STEP 1: 新規予約リクエスト -->
    <section class="step-card">
      <div class="step-header">
        <div class="step-number">1</div>
        <h2 class="step-title">新規予約リクエスト</h2>
      </div>
      
      <!-- 予約回数の説明 -->
      <div style="background: linear-gradient(135deg, #E3F2FD, #BBDEFB); border-left: 4px solid #2196F3; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <p style="margin: 0; color: #1565C0; font-weight: 600; font-size: 1rem;">
          <i data-lucide="info" style="width: 20px; height: 20px; display: inline; vertical-align: text-bottom;"></i>
          受験者は全部で<strong style="font-size: 1.2rem; color: #0D47A1;">3回</strong>予約ができます
        </p>
        <p style="margin: 10px 0 0 0; color: #1976D2; font-size: 0.95rem;">
          現在の予約状況: <strong><?= $total_reservations ?> / <?= $max_reservations ?>回</strong>
        </p>
      </div>
      
      <?php if ($can_reserve): ?>
        <a href="mypage/reserve/new.php" class="btn-action">
          <i data-lucide="calendar-plus"></i>
          練習セッションを予約する
        </a>
      <?php else: ?>
        <div style="background: #FFEBEE; border-left: 4px solid #F44336; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
          <p style="margin: 0; color: #C62828; font-weight: 600;">
            <i data-lucide="alert-circle" style="width: 20px; height: 20px; display: inline; vertical-align: text-bottom;"></i>
            予約上限に達しています（<?= $total_reservations ?> / <?= $max_reservations ?>回）
          </p>
          <p style="margin: 10px 0 0 0; color: #666; font-size: 0.95rem;">
            ※ このPoC版では最大<?= $max_reservations ?>回まで予約可能です。
          </p>
        </div>
      <?php endif; ?>
      
      <!-- 練習時間の詳細説明 -->
      <div class="info-section">
        <h4>
          <i data-lucide="clock"></i>
          1回の練習セッションについて
        </h4>
        <ul>
          <li><strong>ペルソナお渡し:</strong> 約5分</li>
          <li><strong>面接試験:</strong> 20分（15分のロールプレイ＋5分の口頭試問）</li>
          <li><strong>相互フィードバック:</strong> 約15分</li>
        </ul>
        <div class="total-time">
          合計: 約45分
        </div>
      </div>
      
      <!-- 試験日程情報 -->
      <div class="exam-info">
        <p>
          <i data-lucide="info" style="width: 18px; height: 18px; display: inline; vertical-align: text-bottom;"></i>
          <strong>第31回実技試験日:</strong> 2026年3月7日,8日,13日,14日,15日,20日,21日,22日
        </p>
      </div>
    </section>

    <!-- STEP 2: 承認待ちリクエスト -->
    <section class="step-card">
      <div class="step-header">
        <div class="step-number">2</div>
        <h2 class="step-title">
          承認待ちリクエスト
          <span class="count-badge <?php echo count($pending_sessions) > 0 ? '' : 'none'; ?>">
            <?php echo count($pending_sessions); ?>
          </span>
        </h2>
      </div>
      
      <?php if (count($pending_sessions) > 0): ?>
        <div class="reservation-grid">
          <?php foreach ($pending_sessions as $session): ?>
            <div class="reservation-card">
              <div class="info-box">
                <div class="info-label">
                  <i data-lucide="calendar"></i>
                  希望日時
                </div>
                <div class="info-value">
                  <?php echo date('Y年n月j日', strtotime($session['meeting_date'])); ?>
                  (<?php echo getJapaneseWeekday($session['meeting_date']); ?>)
                  <?php echo date('H:i', strtotime($session['meeting_date'])); ?>
                </div>
              </div>
              
              <div class="warning-message">
                <i data-lucide="clock" style="width: 16px; height: 16px; display: inline; vertical-align: text-bottom;"></i>
                キャリアコンサルタントの承認をお待ちください
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i data-lucide="inbox" style="width: 64px; height: 64px; opacity: 0.3;"></i>
          <p>承認待ちのリクエストはありません</p>
        </div>
      <?php endif; ?>
    </section>

    <!-- STEP 3: 確定したセッション（実施予定） -->
    <section class="step-card">
      <div class="step-header">
        <div class="step-number">3</div>
        <h2 class="step-title">
          確定したセッション（実施予定）
          <span class="count-badge <?php echo count($confirmed_sessions) > 0 ? '' : 'none'; ?>">
            <?php echo count($confirmed_sessions); ?>
          </span>
        </h2>
      </div>
      
      <?php if (count($confirmed_sessions) > 0): ?>
        <div class="reservation-grid">
          <?php foreach ($confirmed_sessions as $session): ?>
            <div class="reservation-card">
              <div class="card-row">
                <div class="info-box" style="flex: 1;">
                  <div class="info-label">
                    <i data-lucide="calendar"></i>
                    実施日時
                  </div>
                  <div class="info-value">
                    <?php echo date('Y年n月j日', strtotime($session['meeting_date'])); ?>
                    (<?php echo getJapaneseWeekday($session['meeting_date']); ?>)
                    <?php echo date('H:i', strtotime($session['meeting_date'])); ?>
                  </div>
                </div>
              </div>
              
              <?php if ($session['trainer_name']): ?>
                <div class="info-box">
                  <div class="info-label">
                    <i data-lucide="user"></i>
                    担当キャリアコンサルタント
                  </div>
                  <div class="info-value">
                    <?php echo h($session['trainer_name']); ?>
                  </div>
                </div>
              <?php endif; ?>
              
              <div class="persona-box">
                <div class="persona-title">
                  <i data-lucide="user-circle"></i>
                  ロールプレイのペルソナ
                </div>
                <div class="persona-details" style="color: #666;">
                  ペルソナは当日にお知らせします
                </div>
              </div>
              
              <?php if ($session['meeting_url']): ?>
                <div class="meet-url">
                  <div class="info-label" style="margin-bottom: 8px;">
                    <i data-lucide="video"></i>
                    Google Meet
                  </div>
                  <a href="<?php echo h($session['meeting_url']); ?>" target="_blank">
                    <?php echo h($session['meeting_url']); ?>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i data-lucide="calendar-x" style="width: 64px; height: 64px; opacity: 0.3;"></i>
          <p>確定したセッションはありません</p>
        </div>
      <?php endif; ?>
    </section>

    <!-- STEP 4: 完了したセッション -->
    <section class="step-card">
      <div class="step-header">
        <div class="step-number">4</div>
        <h2 class="step-title">
          完了したセッション
          <span class="count-badge completed">
            <?php echo count($completed_sessions); ?> / 3
          </span>
        </h2>
      </div>
      
      <?php if (count($completed_sessions) > 0): ?>
        <div class="reservation-grid">
          <?php foreach ($completed_sessions as $session): ?>
            <div class="reservation-card">
              <div class="card-row">
                <div class="info-box" style="flex: 1;">
                  <div class="info-label">
                    <i data-lucide="calendar-check"></i>
                    実施日
                  </div>
                  <div class="info-value">
                    <?php echo date('Y年n月j日', strtotime($session['meeting_date'])); ?>
                    (<?php echo getJapaneseWeekday($session['meeting_date']); ?>)
                  </div>
                </div>
              </div>
              
              <?php if ($session['trainer_name']): ?>
                <div class="info-box">
                  <div class="info-label">
                    <i data-lucide="user"></i>
                    担当キャリアコンサルタント
                  </div>
                  <div class="info-value">
                    <?php echo h($session['trainer_name']); ?>
                  </div>
                </div>
              <?php endif; ?>
              
              <?php if ($session['feedback_comment']): ?>
                <a href="mypage/reserve/feedback/view.php?id=<?php echo $session['id']; ?>" class="btn-view">
                  <i data-lucide="file-text"></i>
                  フィードバックを見る
                </a>
              <?php else: ?>
                <div class="warning-message">
                  フィードバック準備中
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i data-lucide="history" style="width: 64px; height: 64px; opacity: 0.3;"></i>
          <p>完了したセッションはまだありません</p>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>
</body>
</html>
