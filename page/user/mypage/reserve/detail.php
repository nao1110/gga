<?php
// 認証チェック
require_once __DIR__ . '/../../../../lib/validation.php';
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/helpers.php';
require_once __DIR__ . '/../../../../lib/database.php';
requireLogin('user');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$user_id = $current_user['id'];
$user_name = $current_user['name'];

// 予約IDを取得
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reservation_id === 0) {
    header('Location: ../../mypage.php');
    exit;
}

// データベース接続
$db = getDBConnection();

// 予約詳細データ取得
$stmt = $db->prepare("
    SELECT 
        r.id,
        r.meeting_date,
        r.status,
        r.meeting_url,
        r.created_at,
        t.id as trainer_id,
        t.name as trainer_name,
        p.id as persona_id,
        p.persona_name,
        p.age,
        p.family_structure,
        p.job,
        p.situation,
        f.comment as feedback_comment,
        f.created_at as feedback_at,
        rep.comment as report_comment,
        rep.created_at as report_at
    FROM reserves r
    LEFT JOIN trainers t ON r.trainer_id = t.id
    LEFT JOIN personas p ON r.persona_id = p.id
    LEFT JOIN feedbacks f ON r.id = f.reserve_id
    LEFT JOIN reports rep ON r.id = rep.reserve_id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$reservation_id, $user_id]);
$reservation = $stmt->fetch();

// 予約が存在しない、または他人の予約
if (!$reservation) {
    header('Location: ../../mypage.php');
    exit;
}

// JSONデータをデコード
$feedback_data = null;
$self_feedback_data = null;

if ($reservation['feedback_comment']) {
    $feedback_data = json_decode($reservation['feedback_comment'], true);
}

if ($reservation['report_comment']) {
    $self_feedback_data = json_decode($reservation['report_comment'], true);
}

// ペルソナ情報の整形
$persona = [
    'id' => $reservation['persona_id'],
    'name' => $reservation['persona_name'],
    'age' => $reservation['age'],
    'family' => $reservation['family_structure'],
    'job' => $reservation['job'],
    'situation' => $reservation['situation']
];

// フィードバックデータの整形
$feedback = null;
if ($feedback_data) {
    $feedback = [
        'strengths' => $feedback_data['strengths'] ?? [],
        'improvements' => $feedback_data['improvements'] ?? [],
        'next_goals' => $feedback_data['next_goals'] ?? [],
        'comment' => $feedback_data['overall_comment'] ?? ''
    ];
}

// 自己評価データの整形
$self_feedback = null;
if ($self_feedback_data) {
    $self_feedback = [
        'satisfaction' => $self_feedback_data['satisfaction'] ?? 0,
        'strengths' => $self_feedback_data['strengths'] ?? '',
        'challenges' => $self_feedback_data['challenges'] ?? '',
        'learnings' => $self_feedback_data['learnings'] ?? '',
        'next_goals' => $self_feedback_data['next_goals'] ?? ''
    ];
}

// 録画情報（現時点では未実装）
$recording = [
    'available' => false,
    'url' => null,
    'duration' => null,
    'file_size' => null,
    'thumbnail' => null
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>予約詳細 - CareerTre キャリアトレーナーズ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../../../assets/css/variables.css">
  <link rel="stylesheet" href="../../../../assets/css/custom.css">
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
          <a href="../../mypage.php" class="nav-link">
            <i data-lucide="home"></i>
            <span>マイページ</span>
          </a>
          <a href="../../profile.php" class="nav-link">
            <i data-lucide="user"></i>
            <span>プロフィール</span>
          </a>
          <a href="../../logout.php" class="nav-link">
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
      <header class="page-header fade-in">
        <a href="../reserve.php" class="back-link">
          <i data-lucide="arrow-left"></i>
          予約一覧に戻る
        </a>
        <h2 class="page-title">練習詳細</h2>
        <p class="page-description">ID: <?php echo $reservation['id']; ?></p>
      </header>

      <!-- ステータスバナー -->
      <section class="status-banner fade-in">
        <?php if ($reservation['status'] === 'completed'): ?>
          <div class="banner completed">
            <i data-lucide="check-circle-2"></i>
            <div class="banner-content">
              <h3>練習完了</h3>
              <p>この練習セッションは完了しました</p>
            </div>
          </div>
        <?php elseif ($reservation['status'] === 'confirmed'): ?>
          <div class="banner confirmed">
            <i data-lucide="calendar-check"></i>
            <div class="banner-content">
              <h3>予約確定</h3>
              <p>面接の準備をしてお待ちください</p>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <!-- シングルカラムレイアウト -->
      <div class="detail-single-column">

        <!-- 左カラム：基本情報 -->
        <!--  -->
          
          <!-- 基本情報カード -->
          <article class="detail-card fade-in">
            <div class="detail-card-header">
              <h3>
                <i data-lucide="info"></i>
                基本情報
              </h3>
            </div>
            <div class="detail-card-body">
              <div class="info-grid">
                <div class="info-item">
                  <div class="info-label">
                    <i data-lucide="calendar"></i>
                    実施日
                  </div>
                  <div class="info-value">
                    <?php 
                      $date = new DateTime($reservation['meeting_date']);
                      echo $date->format('Y年n月j日');
                    ?>
                    （<?php 
                      $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                      echo $weekdays[$date->format('w')];
                    ?>）
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-label">
                    <i data-lucide="clock"></i>
                    時間
                  </div>
                  <div class="info-value">
                    <?php echo $date->format('H:i'); ?> 〜
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-label">
                    <i data-lucide="user"></i>
                    担当コンサルタント
                  </div>
                  <div class="info-value">
                    <?php echo h($reservation['trainer_name']); ?>
                  </div>
                </div>
              </div>
            </div>
          </article>

          <!-- 録画視聴カード -->
          <?php if ($reservation['status'] === 'completed' && $reservation['recording']['available']): ?>
          <article class="detail-card fade-in">
            <div class="detail-card-header">
              <h3>
                <i data-lucide="video"></i>
                練習録画
              </h3>
            </div>
            <div class="detail-card-body">
              <div class="recording-player">
                <div class="video-placeholder">
                  <i data-lucide="play-circle"></i>
                  <p>録画を再生</p>
                </div>
                <!-- 実際の実装ではvideoタグまたはiframeで埋め込み -->
                <!-- <video controls src="<?php echo $reservation['recording']['url']; ?>"></video> -->
              </div>
              
              <div class="recording-info">
                <div class="recording-meta">
                  <span class="meta-badge">
                    <i data-lucide="clock"></i>
                    <?php echo $reservation['recording']['duration']; ?>
                  </span>
                  <span class="meta-badge">
                    <i data-lucide="hard-drive"></i>
                    <?php echo $reservation['recording']['file_size']; ?>
                  </span>
                </div>
              </div>
            </div>
          </article>

          <!-- ペルソナ情報カード（完了時のみ表示） -->
          <?php if ($reservation['status'] === 'completed' && $persona['id']): ?>
          <article class="detail-card fade-in">
            <div class="detail-card-header">
              <h3>
                <i data-lucide="user-circle"></i>
                ペルソナ：<?php echo h($persona['name']); ?>
              </h3>
            </div>
            <div class="detail-card-body">
              <div class="persona-info">
                <div class="persona-item">
                  <div class="persona-label">
                    <i data-lucide="user"></i>
                    基本情報
                  </div>
                  <div class="persona-value">
                    <?php echo h($persona['age']); ?>歳 / <?php echo h($persona['family']); ?>
                  </div>
                </div>

                <div class="persona-item">
                  <div class="persona-label">
                    <i data-lucide="briefcase"></i>
                    職業
                  </div>
                  <div class="persona-value">
                    <?php echo h($persona['job']); ?>
                  </div>
                </div>

                <div class="persona-item">
                  <div class="persona-label">
                    <i data-lucide="message-square"></i>
                    相談状況
                  </div>
                  <div class="persona-quote">
                    <?php echo h($persona['situation']); ?>
                  </div>
                </div>
                </div>
              </div>
            </div>
          </article>
          <?php endif; ?>
          <?php endif; ?>

          <!-- 自己フィードバックカード -->
          <?php if ($reservation['status'] === 'completed'): ?>
          <article class="detail-card fade-in">
            <div class="detail-card-header">
              <h3>
                <i data-lucide="edit"></i>
                あなたの自己フィードバック
              </h3>
              <a href="feedback/detail.php?id=<?php echo $reservation_id; ?>" class="btn-secondary btn-sm">
                <i data-lucide="pencil"></i>
                <?php echo $self_feedback ? '編集' : '入力'; ?>
              </a>
            </div>
            <div class="detail-card-body">
              <?php if ($self_feedback): ?>
                <div class="self-feedback-content">
                  <!-- 満足度 -->
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="star"></i>
                      満足度
                    </h4>
                    <div class="satisfaction-stars">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i data-lucide="star" <?php echo $i <= $self_feedback['satisfaction'] ? 'fill="currentColor"' : ''; ?> style="color: var(--primary);"></i>
                      <?php endfor; ?>
                      <span class="satisfaction-text"><?php echo $self_feedback['satisfaction']; ?> / 5</span>
                    </div>
                  </div>

                  <!-- できたこと・良かった点 -->
                  <?php if (!empty($self_feedback['strengths'])): ?>
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="thumbs-up"></i>
                      できたこと・良かった点
                    </h4>
                    <div class="feedback-text">
                      <?php echo nl2br(h($self_feedback['strengths'])); ?>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- 難しかったこと・課題 -->
                  <?php if (!empty($self_feedback['challenges'])): ?>
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="alert-circle"></i>
                      難しかったこと・課題
                    </h4>
                    <div class="feedback-text">
                      <?php echo nl2br(h($self_feedback['challenges'])); ?>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- 学んだこと・気づき -->
                  <?php if (!empty($self_feedback['learnings'])): ?>
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="lightbulb"></i>
                      学んだこと・気づき
                    </h4>
                    <div class="feedback-text">
                      <?php echo nl2br(h($self_feedback['learnings'])); ?>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- 次回に向けた目標 -->
                  <?php if (!empty($self_feedback['next_goals'])): ?>
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="target"></i>
                      次回に向けた目標
                    </h4>
                    <div class="feedback-text">
                      <?php echo nl2br(h($self_feedback['next_goals'])); ?>
                    </div>
                  </div>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="empty-feedback">
                  <div class="empty-icon">
                    <i data-lucide="pencil"></i>
                  </div>
                  <p class="empty-title">まだ自己フィードバックが入力されていません</p>
                  <p class="empty-description">練習を振り返り、自己評価を記録しましょう</p>
                  <a href="feedback/detail.php?id=<?php echo $reservation_id; ?>" class="btn-primary">
                    <i data-lucide="edit"></i>
                    自己フィードバックを入力する
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </article>
          <?php endif; ?>

        <!-- 右カラム：フィードバック -->
        <!--  -->
          
          <!-- キャリアコンサルタントからのフィードバックカード -->
          <?php if ($reservation['status'] === 'completed' && $feedback): ?>
          <article class="detail-card fade-in">
            <div class="detail-card-header">
              <h3>
                <i data-lucide="message-square"></i>
                キャリアコンサルタントからのフィードバック
              </h3>
            </div>
            <div class="detail-card-body">
              
              <!-- 良かった点 -->
              <?php if (!empty($feedback['strengths'])): ?>
              <div class="feedback-section">
                <h4 class="feedback-title">
                  <i data-lucide="thumbs-up"></i>
                  良かった点
                </h4>
                <ul class="feedback-list success">
                  <?php foreach ($feedback['strengths'] as $strength): ?>
                    <li>
                      <i data-lucide="check"></i>
                      <?php echo h($strength); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <?php endif; ?>

              <!-- 改善点 -->
              <?php if (!empty($feedback['improvements'])): ?>
              <div class="feedback-section">
                <h4 class="feedback-title">
                  <i data-lucide="alert-circle"></i>
                  改善点
                </h4>
                <ul class="feedback-list warning">
                  <?php foreach ($feedback['improvements'] as $improvement): ?>
                    <li>
                      <i data-lucide="arrow-right"></i>
                      <?php echo h($improvement); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <?php endif; ?>

              <!-- 次回の目標 -->
              <?php if (!empty($feedback['next_goals'])): ?>
              <div class="feedback-section">
                <h4 class="feedback-title">
                  <i data-lucide="target"></i>
                  次回の目標
                </h4>
                <ul class="feedback-list info">
                  <?php foreach ($feedback['next_goals'] as $goal): ?>
                    <li>
                      <i data-lucide="flag"></i>
                      <?php echo h($goal); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <?php endif; ?>

              <!-- コメント -->
              <?php if (!empty($feedback['comment'])): ?>
              <div class="feedback-comment">
                <h4 class="feedback-title">
                  <i data-lucide="message-circle"></i>
                  コンサルタントからのコメント
                </h4>
                <div class="comment-box">
                  <p><?php echo nl2br(h($feedback['comment'])); ?></p>
                  <div class="comment-author">
                    <i data-lucide="user"></i>
                    <?php echo h($reservation['trainer_name']); ?>
                  </div>
                </div>
              </div>
              <?php endif; ?>
              </div>

            </div>
          </article>

          <?php else: ?>
          
          <!-- フィードバック待ち -->
          <article class="detail-card fade-in">
            <div class="detail-card-header">
              <h3>
                <i data-lucide="clock"></i>
                フィードバック
              </h3>
            </div>
            <div class="detail-card-body">
              <div class="empty-feedback">
                <i data-lucide="file-text"></i>
                <h4>フィードバックはまだありません</h4>
                <p>練習完了後、コンサルタントからフィードバックが届きます。</p>
              </div>
            </div>
          </article>

          <?php endif; ?>

        </div>

      </div>

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
