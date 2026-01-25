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
// pending: trainer_idがNULLまたは自分のID（未割り当てまたは割り当て済み）
// confirmed/completed: 自分に割り当てられたもののみ
// 各受験者の完了済み実技練習回数もカウント
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
        p.age as persona_age,
        p.family_structure as persona_family,
        p.job as persona_job,
        p.situation as persona_situation,
        f.id as feedback_id,
        f.created_at as feedback_date,
        (
            SELECT COUNT(*) 
            FROM reserves r2 
            WHERE r2.user_id = r.user_id 
            AND r2.status = 'completed'
            AND r2.meeting_date < r.meeting_date
        ) as completed_count
    FROM reserves r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN personas p ON r.persona_id = p.id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    WHERE (r.status = 'pending' AND (r.trainer_id IS NULL OR r.trainer_id = ?))
       OR (r.status IN ('confirmed', 'completed') AND r.trainer_id = ?)
    ORDER BY r.meeting_date ASC
");
$stmt->execute([$current_user['id'], $current_user['id']]);
$all_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ペルソナを動的に割り当てる関数
function assignPersona($pdo, $user_id, $completed_count) {
    // 完了回数に基づいてペルソナIDを決定（1-3をループ）
    $persona_number = ($completed_count % 3) + 1;
    
    // ペルソナ情報を取得
    $stmt = $pdo->prepare("SELECT id, persona_name, age, family_structure, job, situation FROM personas WHERE id = ?");
    $stmt->execute([$persona_number]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ステータスごとに分類
$pending_reservations = [];
$confirmed_reservations = [];
$completed_sessions = [];

foreach ($all_reservations as $reservation) {
    // ペルソナが未割り当ての場合、動的に割り当て
    if (!$reservation['persona_id']) {
        $persona = assignPersona($pdo, $reservation['user_id'], $reservation['completed_count']);
        if ($persona) {
            $reservation['persona_id'] = $persona['id'];
            $reservation['persona_name'] = $persona['persona_name'];
            $reservation['persona_age'] = $persona['age'];
            $reservation['persona_family'] = $persona['family_structure'];
            $reservation['persona_job'] = $persona['job'];
            $reservation['persona_situation'] = $persona['situation'];
        }
    }
    
    if ($reservation['status'] === 'pending') {
        $pending_reservations[] = $reservation;
    } elseif ($reservation['status'] === 'confirmed') {
        $confirmed_reservations[] = $reservation;
    } elseif ($reservation['status'] === 'completed') {
        $completed_sessions[] = $reservation;
    }
}

// 完了セッションの総数を保存
$total_completed_count = count($completed_sessions);

// 全件表示フラグをチェック
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';

// 完了セッションの表示件数を決定
if ($show_all) {
    $completed_sessions_display = $completed_sessions;
} else {
    $completed_sessions_display = array_slice($completed_sessions, 0, 3);
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
    
    .trainer-header {
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
      transition: transform 0.3s;
    }
    
    .step-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 20px rgba(255, 152, 0, 0.2);
    }
    
    .step-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
      padding-bottom: 15px;
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
    
    .step-subtitle {
      color: #666;
      font-size: 0.9rem;
      margin-top: 5px;
    }
    
    .profile-status {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      background: #E8F5E9;
      color: #2E7D32;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
    }
    
    .profile-status.incomplete {
      background: #FFF3E0;
      color: #E65100;
    }
    
    .badge-count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 28px;
      height: 28px;
      background: #FF5722;
      color: white;
      border-radius: 50%;
      font-weight: 700;
      font-size: 0.9rem;
      padding: 0 8px;
    }
    
    .reservation-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
      gap: 20px;
    }
    
    @media (max-width: 768px) {
      .reservation-grid {
        grid-template-columns: 1fr;
      }
    }
    
    .reservation-card {
      background: #FAFAFA;
      border: 2px solid #FFE0B2;
      border-radius: 12px;
      padding: 30px;
      transition: all 0.3s;
      height: fit-content;
    }
    
    .reservation-card:hover {
      border-color: #FF9800;
      box-shadow: 0 4px 12px rgba(255, 152, 0, 0.2);
    }
    
    .reservation-card.pending {
      border-left: 5px solid #FF9800;
      background: #FFF8E1;
    }
    
    .reservation-card.confirmed {
      border-left: 5px solid #4CAF50;
      background: #F1F8F4;
    }
    
    .reservation-card.completed {
      border-left: 5px solid #9E9E9E;
    }
    
    .card-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #FFE0B2;
      flex-wrap: wrap;
      gap: 15px;
    }
    
    .user-info-section {
      flex: 1;
      min-width: 250px;
    }
    
    .user-label {
      display: inline-block;
      padding: 6px 14px;
      background: #FF9800;
      color: white;
      border-radius: 5px;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 10px;
    }
    
    .user-name-large {
      font-size: 1.5rem;
      font-weight: 700;
      color: #333;
      margin: 8px 0 0 0;
    }
    
    .persona-tag {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 14px;
      background: linear-gradient(135deg, #9C27B0, #7B1FA2);
      color: white;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    .persona-details {
      background: white;
      border-radius: 10px;
      padding: 15px;
      margin: 15px 0;
    }
    
    .persona-name {
      font-size: 1.1rem;
      font-weight: 700;
      color: #7B1FA2;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .detail-row {
      display: flex;
      gap: 10px;
      margin: 8px 0;
      font-size: 0.95rem;
    }
    
    .detail-label {
      font-weight: 600;
      color: #666;
      min-width: 100px;
    }
    
    .detail-value {
      color: #333;
    }
    
    .meeting-info {
      display: grid;
      gap: 15px;
      margin: 20px 0;
      background: white;
      padding: 20px;
      border-radius: 10px;
      border: 1px solid #E0E0E0;
    }
    
    .info-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1rem;
    }
    
    .info-item svg {
      color: #FF9800;
      flex-shrink: 0;
    }
    
    .info-label {
      font-weight: 600;
      color: #666;
      min-width: 80px;
    }
    
    .info-value {
      color: #333;
      font-weight: 500;
    }
    
    .meeting-url-box {
      background: #E3F2FD;
      padding: 12px;
      border-radius: 8px;
      border: 2px dashed #2196F3;
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 10px;
    }
    
    .meeting-url-box a {
      color: #2196F3;
      text-decoration: none;
      font-weight: 600;
      word-break: break-all;
      flex: 1;
    }
    
    .btn-copy {
      background: #2196F3;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.85rem;
    }
    
    .btn-copy:hover {
      background: #1976D2;
    }
    
    .card-actions {
      display: flex;
      gap: 10px;
      margin-top: 15px;
      flex-wrap: wrap;
    }
    
    .btn {
      padding: 12px 24px;
      border-radius: 8px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      transition: all 0.3s;
      font-size: 0.95rem;
    }
    
    .btn-approve {
      background: linear-gradient(135deg, #4CAF50, #388E3C);
      color: white;
    }
    
    .btn-approve:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    }
    
    .btn-feedback {
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: white;
    }
    
    .btn-feedback:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
    }
    
    .btn-view {
      background: white;
      color: #FF9800;
      border: 2px solid #FF9800;
    }
    
    .btn-view:hover {
      background: #FFF8E1;
    }
    
    .btn-profile {
      background: white;
      color: #FF9800;
      border: 2px solid #FF9800;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }
    
    .btn-profile:hover {
      background: #FF9800;
      color: white;
    }
    
    .btn-admin {
      background: linear-gradient(135deg, #E65100, #FF6F00);
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
      border: none;
      box-shadow: 0 2px 8px rgba(230, 81, 0, 0.3);
    }
    
    .btn-admin:hover {
      background: linear-gradient(135deg, #BF360C, #E65100);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(230, 81, 0, 0.4);
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
      border: none;
    }
    
    .btn-logout:hover {
      background: #757575;
    }
    
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }
    
    .empty-state svg {
      width: 80px;
      height: 80px;
      color: #DDD;
      margin-bottom: 20px;
    }
    
    .empty-state p {
      font-size: 1.1rem;
      margin: 0;
    }
    
    .success-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      background: #E8F5E9;
      color: #2E7D32;
      border-radius: 15px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    .alert-success {
      background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
      border-left: 5px solid #4CAF50;
      padding: 15px 20px;
      border-radius: 10px;
      margin-bottom: 30px;
      color: #2E7D32;
      font-weight: 600;
    }
    
    footer {
      text-align: center;
      padding: 40px 20px;
      color: #999;
      margin-top: 60px;
    }
  </style>
</head>
<body>
  <!-- ヘッダー -->
  <header class="trainer-header">
    <div class="header-content">
      <div class="logo-section">
        <h1>キャリアトレーナーズ</h1>
        <p>Career Trainers - 有資格者マイページ</p>
      </div>
      <div class="header-actions">
        <a href="profile.php" class="btn-profile">
          <i data-lucide="user"></i>
          プロフィール編集
        </a>
        <div class="user-badge">
          <i data-lucide="graduation-cap"></i>
          <?php echo h($trainer_name); ?>
        </div>
        <?php if ($current_user['email'] === 'naoko.s1110@gmail.com'): ?>
        <a href="../admin/dashboard.php" class="btn-admin">
          <i data-lucide="shield"></i>
          管理者画面
        </a>
        <?php endif; ?>
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
      <i data-lucide="check-circle" style="display: inline; width: 20px; height: 20px;"></i>
      <?= h($success) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- STEP 1: プロフィール -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">1</div>
        <div>
          <h2 class="step-title">プロフィール設定</h2>
          <p class="step-subtitle">受験者にあなたの情報を提供しましょう</p>
        </div>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
          <?php if ($trainer_specialty): ?>
            <span class="profile-status">
              <i data-lucide="check-circle"></i>
              プロフィール登録済み
            </span>
            <p style="margin: 10px 0 0 0; color: #666;">専門分野: <?php echo h($trainer_specialty); ?></p>
          <?php else: ?>
            <span class="profile-status incomplete">
              <i data-lucide="alert-circle"></i>
              プロフィール未登録
            </span>
          <?php endif; ?>
        </div>
        <a href="profile.php" class="btn btn-feedback">
          <i data-lucide="edit"></i>
          プロフィールを編集
        </a>
      </div>
    </div>
  </div>

  <!-- STEP 2: 予約リクエストの承認 -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">2</div>
        <div style="flex: 1;">
          <h2 class="step-title">予約リクエストの承認</h2>
          <p class="step-subtitle">
            受験者からの実技練習リクエストを確認して承認しましょう。<br>
            あなたが確実にオンラインで実技練習を対応できる日にしてください。<br>
            <span style="color: #E65100; font-weight: 600;">※承認後は、変更できません。変更する場合は管理者へお問い合わせください。</span>
          </p>
        </div>
        <span class="badge-count" style="<?php echo empty($pending_reservations) ? 'background: #9E9E9E;' : ''; ?>">
          <?php echo count($pending_reservations); ?> 件
        </span>
      </div>
      
      <?php if (!empty($pending_reservations)): ?>
        <div class="reservation-grid">
          <?php foreach ($pending_reservations as $reservation): ?>
          <div class="reservation-card pending">
            <div class="card-top">
              <div class="user-info-section">
                <span class="user-label">受験者</span>
                <h3 class="user-name-large"><?php echo h($reservation['user_name']); ?> さん</h3>
              </div>
            </div>
            
            <div class="meeting-info">
              <div class="info-item">
                <i data-lucide="calendar"></i>
                <span class="info-label">日付：</span>
                <span class="info-value"><?php echo h(date('Y年m月d日', strtotime($reservation['meeting_date']))); ?>（<?php echo getJapaneseWeekday($reservation['meeting_date']); ?>）</span>
              </div>
              <div class="info-item">
                <i data-lucide="clock"></i>
                <span class="info-label">時間：</span>
                <span class="info-value"><?php 
                  $start_time = date('H:i', strtotime($reservation['meeting_date']));
                  $end_time = date('H:i', strtotime($reservation['meeting_date'] . ' +1 hour'));
                  echo h($start_time . ' - ' . $end_time . ' (1時間)');
                ?></span>
              </div>
              <div class="info-item">
                <i data-lucide="info"></i>
                <span class="info-label">申請日：</span>
                <span class="info-value" style="color: #999;"><?php echo h(date('Y年m月d日', strtotime($reservation['created_at']))); ?></span>
              </div>
            </div>
            
            <div class="card-actions">
              <form action="../../controller/trainer/reserve_approve.php" method="POST" style="display: inline;">
                <input type="hidden" name="reserve_id" value="<?php echo h($reservation['id']); ?>">
                <button type="submit" class="btn btn-approve" onclick="return confirm('この予約を承認しますか？');">
                  <i data-lucide="check"></i>
                  承認する
                </button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i data-lucide="inbox"></i>
          <p>現在、承認待ちの予約リクエストはありません</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- STEP 3: 実施予定の実技練習 -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">3</div>
        <div style="flex: 1;">
          <h2 class="step-title">実施予定の実技練習</h2>
          <p class="step-subtitle">
            承認済みのセッション・Google MeetのURLを確認しましょう。<br>
            <span style="color: #E65100; font-weight: 600;">※参加者はペルソナを知りません。</span><br>
            開始してからペルソナをお伝えし開始してください。別途、実技練習マニュアルを参照の上当日実施してください。
          </p>
        </div>
        <span class="badge-count" style="background: <?php echo empty($confirmed_reservations) ? '#9E9E9E' : '#4CAF50'; ?>;">
          <?php echo count($confirmed_reservations); ?> 件
        </span>
      </div>
      
      <?php if (!empty($confirmed_reservations)): ?>
        <div class="reservation-grid">
          <?php foreach ($confirmed_reservations as $reservation): ?>
          <div class="reservation-card confirmed">
            <div class="card-top">
              <div class="user-info-section">
                <span class="user-label" style="background: #4CAF50;">受験者</span>
                <h3 class="user-name-large"><?php echo h($reservation['user_name']); ?> さん</h3>
              </div>
              <?php if ($reservation['persona_id']): ?>
                <span class="persona-tag">
                  <i data-lucide="user-circle"></i>
                  ペルソナ<?php echo h($reservation['persona_id']); ?>
                </span>
              <?php endif; ?>
            </div>
            
            <?php if ($reservation['persona_name']): ?>
            <div class="persona-details">
              <div class="persona-name">
                <i data-lucide="user"></i>
                相談者役: <?php echo h($reservation['persona_name']); ?> さん（<?php echo h($reservation['persona_age']); ?>歳）
              </div>
              <div class="detail-row">
                <span class="detail-label">家族構成:</span>
                <span class="detail-value"><?php echo h($reservation['persona_family']); ?></span>
              </div>
              <div class="detail-row">
                <span class="detail-label">業種・職種:</span>
                <span class="detail-value"><?php echo h($reservation['persona_job']); ?></span>
              </div>
              <div class="detail-row">
                <span class="detail-label">相談内容:</span>
                <span class="detail-value"><?php echo h($reservation['persona_situation']); ?></span>
              </div>
            </div>
            <?php endif; ?>
            
            <div class="meeting-info">
              <div class="info-item">
                <i data-lucide="calendar"></i>
                <span class="info-label">日付：</span>
                <span class="info-value"><?php echo h(date('Y年m月d日', strtotime($reservation['meeting_date']))); ?>（<?php echo getJapaneseWeekday($reservation['meeting_date']); ?>）</span>
              </div>
              <div class="info-item">
                <i data-lucide="clock"></i>
                <span class="info-label">時間：</span>
                <span class="info-value"><?php 
                  $start_time = date('H:i', strtotime($reservation['meeting_date']));
                  $end_time = date('H:i', strtotime($reservation['meeting_date'] . ' +1 hour'));
                  echo h($start_time . ' - ' . $end_time . ' (1時間)');
                ?></span>
              </div>
            </div>
            
            <?php if ($reservation['meeting_url']): ?>
            <div class="meeting-url-box">
              <i data-lucide="video" style="color: #2196F3;"></i>
              <a href="<?php echo h($reservation['meeting_url']); ?>" target="_blank">
                <?php echo h($reservation['meeting_url']); ?>
              </a>
              <button class="btn-copy" onclick="copyToClipboard('<?php echo h($reservation['meeting_url']); ?>')">
                <i data-lucide="copy"></i>
                コピー
              </button>
            </div>
            <?php else: ?>
            <div class="meeting-url-box" style="background: #FFF3E0; border-color: #FF9800;">
              <i data-lucide="alert-circle" style="color: #FF9800;"></i>
              <span style="color: #E65100; font-weight: 600;">Google MeetのURLが未設定です。Google認証を行うと自動生成されます。</span>
            </div>
            <?php endif; ?>
            
            <div class="card-actions">
              <a href="mypage/reserve/feedback/input.php?id=<?php echo h($reservation['id']); ?>" class="btn btn-feedback">
                <i data-lucide="file-edit"></i>
                レポート入力へ
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i data-lucide="calendar-x"></i>
          <p>現在、実施予定の実技練習はありません</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- STEP 4: レポート入力 -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">4</div>
        <div>
          <h2 class="step-title">レポート入力</h2>
          <p class="step-subtitle">
            実技練習後、受験者へのフィードバックを入力しましょう。<br>
            <span style="color: #E65100; font-weight: 600;">※終了後すぐにご入力ください。</span>
          </p>
        </div>
      </div>
      <div style="background: #F5F5F5; padding: 20px; border-radius: 10px; text-align: center;">
        <i data-lucide="info" style="width: 40px; height: 40px; color: #FF9800; margin-bottom: 10px;"></i>
        <p style="margin: 0; color: #666;">実技練習実施後、上記「STEP 3」のセッションから「レポート入力へ」ボタンをクリックしてレポートを作成してください。</p>
      </div>
    </div>
  </div>

  <!-- STEP 5: 完了した実技練習の実績 -->
  <div class="step-container">
    <div class="step-card">
      <div class="step-header">
        <div class="step-number">5</div>
        <div style="flex: 1;">
          <h2 class="step-title">完了した実技練習の実績</h2>
          <p class="step-subtitle">過去に実施したセッションとレポートを確認できます</p>
        </div>
        <?php if ($total_completed_count > 0): ?>
          <div style="background: linear-gradient(135deg, #4CAF50, #388E3C); color: white; padding: 10px 20px; border-radius: 25px; font-weight: 700; font-size: 1.1rem;">
            合計 <?php echo $total_completed_count; ?> 回実施
          </div>
        <?php endif; ?>
      </div>
      
      <?php if (!empty($completed_sessions_display)): ?>
        <div class="reservation-grid">
          <?php foreach ($completed_sessions_display as $session): ?>
          <div class="reservation-card completed">
            <div class="card-top">
              <div class="user-info-section">
                <span class="user-label" style="background: #9E9E9E;">受験者</span>
                <h3 class="user-name-large"><?php echo h($session['user_name']); ?> さん</h3>
              </div>
              <?php if ($session['feedback_id']): ?>
                <span class="success-badge">
                  <i data-lucide="check-circle"></i>
                  レポート提出済み
                </span>
              <?php endif; ?>
            </div>
            
            <div class="meeting-info">
              <div class="info-item">
                <i data-lucide="calendar"></i>
                <span class="info-label">日付：</span>
                <span class="info-value"><?php echo h(date('Y年m月d日', strtotime($session['meeting_date']))); ?>（<?php echo getJapaneseWeekday($session['meeting_date']); ?>）</span>
              </div>
              <div class="info-item">
                <i data-lucide="clock"></i>
                <span class="info-label">時間：</span>
                <span class="info-value"><?php 
                  $start_time = date('H:i', strtotime($session['meeting_date']));
                  $end_time = date('H:i', strtotime($session['meeting_date'] . ' +1 hour'));
                  echo h($start_time . ' - ' . $end_time . ' (1時間)');
                ?></span>
              </div>
              <?php if ($session['feedback_id']): ?>
              <div class="info-item">
                <i data-lucide="file-check"></i>
                <span style="color: #2E7D32;">レポート提出: <?php echo h(date('Y年m月d日', strtotime($session['feedback_date']))); ?></span>
              </div>
              <?php endif; ?>
            </div>
            
            <div class="card-actions">
              <a href="mypage/reserve/feedback/input.php?id=<?php echo h($session['id']); ?>" class="btn btn-view">
                <i data-lucide="eye"></i>
                レポートを確認
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if (!$show_all && $total_completed_count > 3): ?>
        <div style="text-align: center; margin-top: 20px;">
          <p style="color: #999; font-size: 0.9rem; margin-bottom: 15px;">※ 最新3件を表示しています（全<?php echo $total_completed_count; ?>件中）</p>
          <a href="?show_all=1" class="btn btn-view" style="text-decoration: none;">
            <i data-lucide="list"></i>
            全ての履歴を表示（<?php echo $total_completed_count; ?>件）
          </a>
        </div>
        <?php elseif ($show_all && $total_completed_count > 3): ?>
        <div style="text-align: center; margin-top: 20px;">
          <p style="color: #999; font-size: 0.9rem; margin-bottom: 15px;">※ 全<?php echo $total_completed_count; ?>件を表示しています</p>
          <a href="?" class="btn btn-view" style="text-decoration: none;">
            <i data-lucide="minimize-2"></i>
            最新3件のみ表示
          </a>
        </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="empty-state">
          <i data-lucide="archive"></i>
          <p>まだ完了したセッションはありません</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- フッター -->
  <footer>
    <p>&copy; 2025 キャリアトレーナーズ All rights reserved.</p>
  </footer>

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
