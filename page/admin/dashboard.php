<?php
// 認証チェック
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
    redirect('/gs_code/gga/page/trainer/mypage.php');
}

// データベース接続
$pdo = getDBConnection();

// 統計情報を取得

// 1. トレーナー一覧と統計
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.name,
        t.email,
        t.nickname,
        t.career_description,
        t.created_at,
        COUNT(CASE WHEN r.status = 'confirmed' THEN 1 END) as approved_count,
        COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN r.status = 'completed' AND f.id IS NOT NULL THEN 1 END) as report_count
    FROM trainers t
    LEFT JOIN reserves r ON t.id = r.trainer_id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->execute();
$trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. 受験者一覧と統計
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.created_at,
        COUNT(r.id) as request_count,
        COUNT(CASE WHEN r.status = 'confirmed' THEN 1 END) as confirmed_count,
        COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as completed_count
    FROM users u
    LEFT JOIN reserves r ON u.id = r.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. 予約リクエスト状況
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.status,
        r.created_at,
        u.name as user_name,
        u.email as user_email,
        t.name as trainer_name,
        t.email as trainer_email,
        p.persona_name,
        r.meeting_url
    FROM reserves r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN personas p ON r.persona_id = p.id
    WHERE r.status = 'pending'
    ORDER BY r.meeting_date ASC
");
$stmt->execute();
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. 承認済み/実施予定
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.status,
        r.created_at,
        u.name as user_name,
        u.email as user_email,
        t.name as trainer_name,
        t.email as trainer_email,
        p.persona_name,
        r.meeting_url
    FROM reserves r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN personas p ON r.persona_id = p.id
    WHERE r.status = 'confirmed'
    ORDER BY r.meeting_date ASC
");
$stmt->execute();
$confirmed_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. 完了セッションとレポート状況
$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.status,
        r.created_at,
        u.name as user_name,
        u.email as user_email,
        t.name as trainer_name,
        t.email as trainer_email,
        p.persona_name,
        f.id as feedback_id,
        f.created_at as feedback_date,
        f.comment as feedback_comment
    FROM reserves r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN personas p ON r.persona_id = p.id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    WHERE r.status = 'completed'
    ORDER BY r.meeting_date DESC
");
$stmt->execute();
$completed_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 全体統計
$total_trainers = count($trainers);
$total_users = count($users);
$total_pending = count($pending_requests);
$total_confirmed = count($confirmed_sessions);
$total_completed = count($completed_sessions);
$total_with_feedback = count(array_filter($completed_sessions, function($s) { return $s['feedback_id']; }));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>管理者ダッシュボード - キャリアトレーナーズ</title>
  
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
    
    .admin-header {
      background: linear-gradient(135deg, #E65100, #FF6F00);
      padding: 25px 0;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 40px;
    }
    
    .header-content {
      max-width: 1400px;
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
      color: white;
      margin: 0;
    }
    
    .logo-section p {
      color: rgba(255,255,255,0.9);
      font-size: 0.9rem;
      margin: 5px 0 0 0;
    }
    
    .admin-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px 40px;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    
    .stat-card h3 {
      font-size: 0.9rem;
      color: #666;
      margin: 0 0 10px 0;
      font-weight: 600;
    }
    
    .stat-card .number {
      font-size: 2.5rem;
      font-weight: 900;
      background: linear-gradient(135deg, #FF9800, #F57C00);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin: 0;
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
    
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    
    .data-table th {
      background: #FFF8E1;
      padding: 12px;
      text-align: left;
      font-weight: 600;
      color: #666;
      border-bottom: 2px solid #FFE0B2;
    }
    
    .data-table td {
      padding: 12px;
      border-bottom: 1px solid #F5F5F5;
    }
    
    .data-table tr:hover {
      background: #FAFAFA;
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    .status-pending {
      background: #FFF3E0;
      color: #E65100;
    }
    
    .status-confirmed {
      background: #E8F5E9;
      color: #2E7D32;
    }
    
    .status-completed {
      background: #F5F5F5;
      color: #666;
    }
    
    .btn-view {
      background: white;
      color: #FF9800;
      border: 2px solid #FF9800;
      padding: 6px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.85rem;
      display: inline-block;
      transition: all 0.3s;
    }
    
    .btn-view:hover {
      background: #FF9800;
      color: white;
    }
    
    .feedback-preview {
      max-width: 300px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #666;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <!-- ヘッダー -->
  <header class="admin-header">
    <div class="header-content">
      <div class="logo-section">
        <h1>🛠️ 管理者ダッシュボード</h1>
        <p>キャリアトレーナーズ - 全体管理画面</p>
      </div>
      <div style="display: flex; gap: 15px; align-items: center;">
        <span style="color: white; font-weight: 600;">
          <?php echo h($current_user['name']); ?>（管理者）
        </span>
        <a href="../trainer/mypage.php" style="background: white; color: #E65100; padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: 600;">
          トレーナーページへ
        </a>
        <a href="../../controller/logout.php" style="background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: 600;">
          ログアウト
        </a>
      </div>
    </div>
  </header>

  <div class="admin-container">
    <!-- 統計カード -->
    <div class="stats-grid">
      <div class="stat-card">
        <h3>トレーナー数</h3>
        <p class="number"><?php echo $total_trainers; ?></p>
      </div>
      <div class="stat-card">
        <h3>受験者数</h3>
        <p class="number"><?php echo $total_users; ?></p>
      </div>
      <div class="stat-card">
        <h3>承認待ち</h3>
        <p class="number"><?php echo $total_pending; ?></p>
      </div>
      <div class="stat-card">
        <h3>実施予定</h3>
        <p class="number"><?php echo $total_confirmed; ?></p>
      </div>
      <div class="stat-card">
        <h3>実施完了</h3>
        <p class="number"><?php echo $total_completed; ?></p>
      </div>
      <div class="stat-card">
        <h3>レポート提出</h3>
        <p class="number"><?php echo $total_with_feedback; ?></p>
      </div>
    </div>

    <!-- トレーナー一覧 -->
    <div class="section-card">
      <h2 class="section-title">
        <i data-lucide="users"></i>
        キャリアコンサルタント一覧
      </h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>名前</th>
            <th>ニックネーム</th>
            <th>メールアドレス</th>
            <th>承認回数</th>
            <th>実施回数</th>
            <th>レポート回数</th>
            <th>登録日</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trainers as $trainer): ?>
          <tr>
            <td><?php echo h($trainer['id']); ?></td>
            <td><?php echo h($trainer['name']); ?></td>
            <td><?php echo h($trainer['nickname'] ?: '未設定'); ?></td>
            <td><?php echo h($trainer['email']); ?></td>
            <td><strong><?php echo $trainer['approved_count']; ?>回</strong></td>
            <td><strong><?php echo $trainer['completed_count']; ?>回</strong></td>
            <td><strong><?php echo $trainer['report_count']; ?>回</strong></td>
            <td><?php echo h(date('Y-m-d', strtotime($trainer['created_at']))); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 受験者一覧 -->
    <div class="section-card">
      <h2 class="section-title">
        <i data-lucide="user-check"></i>
        キャリアコンサルタント受験者一覧
      </h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>名前</th>
            <th>メールアドレス</th>
            <th>申請回数</th>
            <th>予約確定</th>
            <th>実施完了</th>
            <th>登録日</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
          <tr>
            <td><?php echo h($user['id']); ?></td>
            <td><?php echo h($user['name']); ?></td>
            <td><?php echo h($user['email']); ?></td>
            <td><strong><?php echo $user['request_count']; ?>回</strong></td>
            <td><strong><?php echo $user['confirmed_count']; ?>回</strong></td>
            <td><strong><?php echo $user['completed_count']; ?>回</strong></td>
            <td><?php echo h(date('Y-m-d', strtotime($user['created_at']))); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 予約リクエスト状況 -->
    <div class="section-card">
      <h2 class="section-title">
        <i data-lucide="clock"></i>
        予約リクエスト申請状況（承認待ち）
      </h2>
      <?php if (!empty($pending_requests)): ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>受験者</th>
            <th>日時</th>
            <th>ペルソナ</th>
            <th>申請日</th>
            <th>ステータス</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending_requests as $req): ?>
          <tr>
            <td><?php echo h($req['id']); ?></td>
            <td><?php echo h($req['user_name']); ?><br><small><?php echo h($req['user_email']); ?></small></td>
            <td><?php echo h(date('Y-m-d H:i', strtotime($req['meeting_date']))); ?></td>
            <td><?php echo h($req['persona_name'] ?: '未割当'); ?></td>
            <td><?php echo h(date('Y-m-d', strtotime($req['created_at']))); ?></td>
            <td><span class="status-badge status-pending">承認待ち</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p style="text-align: center; color: #999; padding: 40px 0;">現在、承認待ちのリクエストはありません</p>
      <?php endif; ?>
    </div>

    <!-- 承認済み/実施予定 -->
    <div class="section-card">
      <h2 class="section-title">
        <i data-lucide="calendar-check"></i>
        実技試験承認/実施予定状況
      </h2>
      <?php if (!empty($confirmed_sessions)): ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>受験者</th>
            <th>トレーナー</th>
            <th>日時</th>
            <th>ペルソナ</th>
            <th>Meet URL</th>
            <th>ステータス</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($confirmed_sessions as $session): ?>
          <tr>
            <td><?php echo h($session['id']); ?></td>
            <td><?php echo h($session['user_name']); ?><br><small><?php echo h($session['user_email']); ?></small></td>
            <td><?php echo h($session['trainer_name']); ?><br><small><?php echo h($session['trainer_email']); ?></small></td>
            <td><?php echo h(date('Y-m-d H:i', strtotime($session['meeting_date']))); ?></td>
            <td><?php echo h($session['persona_name'] ?: '未割当'); ?></td>
            <td><?php echo $session['meeting_url'] ? '<a href="' . h($session['meeting_url']) . '" target="_blank">URL</a>' : '未設定'; ?></td>
            <td><span class="status-badge status-confirmed">承認済み</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p style="text-align: center; color: #999; padding: 40px 0;">現在、実施予定のセッションはありません</p>
      <?php endif; ?>
    </div>

    <!-- 完了セッションとレポート -->
    <div class="section-card">
      <h2 class="section-title">
        <i data-lucide="file-check"></i>
        実施完了・レポート状況
      </h2>
      <?php if (!empty($completed_sessions)): ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>受験者</th>
            <th>トレーナー</th>
            <th>実施日時</th>
            <th>ペルソナ</th>
            <th>レポート</th>
            <th>詳細表示</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($completed_sessions as $session): ?>
          <tr>
            <td><?php echo h($session['id']); ?></td>
            <td><?php echo h($session['user_name']); ?><br><small><?php echo h($session['user_email']); ?></small></td>
            <td><?php echo h($session['trainer_name']); ?><br><small><?php echo h($session['trainer_email']); ?></small></td>
            <td><?php echo h(date('Y-m-d H:i', strtotime($session['meeting_date']))); ?></td>
            <td><?php echo h($session['persona_name'] ?: '未割当'); ?></td>
            <td>
              <?php if ($session['feedback_id']): ?>
                <span class="status-badge status-confirmed">✓ 提出済</span><br>
                <small><?php echo h(date('Y-m-d', strtotime($session['feedback_date']))); ?></small>
              <?php else: ?>
                <span class="status-badge status-pending">未提出</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="report_detail.php?id=<?php echo h($session['id']); ?>" class="btn-view">レポート表示</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p style="text-align: center; color: #999; padding: 40px 0;">まだ完了したセッションはありません</p>
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
