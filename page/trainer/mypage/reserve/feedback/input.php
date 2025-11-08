<?php
// 認証チェック
require_once __DIR__ . '/../../../../../lib/validation.php';
require_once __DIR__ . '/../../../../../lib/auth.php';
require_once __DIR__ . '/../../../../../lib/helpers.php';
require_once __DIR__ . '/../../../../../lib/database.php';
requireLogin('trainer');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$trainer_name = $current_user['name'];

// エラーメッセージ・成功メッセージ取得
$error = getSessionMessage('error');
$errors = getSessionMessage('errors');
$success = getSessionMessage('success');

// 予約IDを取得
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reservation_id) {
    redirect('/gs_code/gga/page/trainer/mypage.php');
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
        r.user_id,
        r.meeting_date,
        r.meeting_url,
        r.status,
        u.name as user_name,
        u.email as user_email,
        p.id as persona_id,
        p.persona_name as persona_name,
        p.age as persona_age,
        p.family_structure as persona_family,
        p.job as persona_job,
        p.situation as persona_situation,
        f.id as feedback_id,
        f.comment as feedback_comment,
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
    WHERE r.id = ? AND r.trainer_id = ?
");
$stmt->execute([$reservation_id, $current_user['id']]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

// 予約が存在しない、または自分の予約でない場合はリダイレクト
if (!$reservation) {
    redirect('/gs_code/gga/page/trainer/mypage.php');
    exit;
}

// ペルソナが未割り当ての場合、動的に割り当て
if (!$reservation['persona_id']) {
    $persona_number = ($reservation['completed_count'] % 5) + 1;
    $stmt = $pdo->prepare("SELECT id, persona_name, age, family_structure, job, situation FROM personas WHERE id = ?");
    $stmt->execute([$persona_number]);
    $persona = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($persona) {
        $reservation['persona_id'] = $persona['id'];
        $reservation['persona_name'] = $persona['persona_name'];
        $reservation['persona_age'] = $persona['age'];
        $reservation['persona_family'] = $persona['family_structure'];
        $reservation['persona_job'] = $persona['job'];
        $reservation['persona_situation'] = $persona['situation'];
    }
}

// フィードバックデータの取得と解析
$is_edit_mode = false;
$feedback_data = null;

if ($reservation['feedback_id'] && $reservation['feedback_comment']) {
    $is_edit_mode = true;
    $feedback_json = json_decode($reservation['feedback_comment'], true);
    
    if ($feedback_json) {
        $feedback_data = [
            'attitude_comment' => $feedback_json['attitude_comment'] ?? '',
            'problem_comment' => $feedback_json['problem_comment'] ?? '',
            'development_comment' => $feedback_json['development_comment'] ?? '',
            'next_advice' => $feedback_json['next_advice'] ?? '',
            'submitted_at' => $reservation['feedback_date']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>フィードバック<?php echo $is_edit_mode ? '確認・編集' : '入力'; ?> - CareerTre キャリトレ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../../../../assets/css/variables.css">
  <link rel="stylesheet" href="../../../../../assets/css/custom.css">
</head>
<body>
  <!-- メインコンテナ -->
  <main class="hero-container">
    <div class="container-narrow">
      
      <!-- ヘッダー -->
      <header class="page-header fade-in">
        <a href="../../mypage.php" class="back-link">
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
              <i data-lucide="user"></i>
              実技練習情報
            </h2>
          </div>
          <div class="card-body">
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">
                  <i data-lucide="user"></i>
                  受験者
                </div>
                <div class="info-value">
                  <?php echo h($reservation['user_name']); ?> さん
                </div>
              </div>

              <div class="info-item">
                <div class="info-label">
                  <i data-lucide="calendar"></i>
                  実施日時
                </div>
                <div class="info-value">
                  <?php echo h(date('Y年m月d日', strtotime($reservation['meeting_date']))); ?>（<?php echo getJapaneseWeekday($reservation['meeting_date']); ?>） <?php echo h(date('H:i', strtotime($reservation['meeting_date']))); ?>
                </div>
              </div>
            </div>
            
            <?php if ($reservation['persona_name']): ?>
            <div class="persona-info" style="margin-top: var(--spacing-lg);">
              <div class="persona-title" style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-md);">
                <span class="role-label role-persona">【相談者役】</span>
                <span class="persona-badge">
                  <i data-lucide="user-circle"></i>
                  ペルソナ<?php echo h($reservation['persona_id']); ?>
                </span>
              </div>
              <h4>
                <i data-lucide="user"></i>
                <?php echo h($reservation['persona_name']); ?> さん（<?php echo h($reservation['persona_age']); ?>歳）
              </h4>
              <p class="persona-job"><strong>家族構成：</strong><?php echo h($reservation['persona_family']); ?></p>
              <p class="persona-job"><strong>業種・職種：</strong><?php echo h($reservation['persona_job']); ?></p>
              <p class="persona-situation"><strong>相談内容：</strong><?php echo h($reservation['persona_situation']); ?></p>
            </div>
            <?php endif; ?>
          </div>
        </article>
      </section>

      <!-- フィードバックフォーム -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="file-edit"></i>
              <?php echo $is_edit_mode ? 'フィードバック確認・編集' : 'フィードバック入力'; ?>
            </h2>
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

            <?php if ($is_edit_mode): ?>
            <div class="alert alert-info">
              <i data-lucide="info"></i>
              <span>提出済みのフィードバックです。編集して再提出することができます。</span>
            </div>
            <?php endif; ?>

            <form action="feedback_process.php" method="POST" class="feedback-form">
              <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
              
              <!-- セクション1：態度・傾聴（基本的姿勢） -->
              <div class="form-section">
                <h3 class="section-title">
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
                
                <div class="form-group">
                  <label for="attitude_comment">具体的なフィードバック <span class="required">*</span></label>
                  <textarea 
                    id="attitude_comment" 
                    name="attitude_comment" 
                    rows="5" 
                    required
                    placeholder="例：笑顔で迎え入れる姿勢が良く、相手が話しやすい雰囲気を作れていました。うなずきやあいづちのタイミングも適切で、受容的な態度を示せています。今後は、さらに共感的な言葉かけ（「それは大変でしたね」など）を増やすと、より信頼関係が深まるでしょう。"
                  ><?php echo $is_edit_mode ? h($feedback_data['attitude_comment'] ?? '') : ''; ?></textarea>
                </div>
              </div>

              <!-- セクション2：問題把握 -->
              <div class="form-section">
                <h3 class="section-title">
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
                
                <div class="form-group">
                  <label for="problem_comment">具体的なフィードバック <span class="required">*</span></label>
                  <textarea 
                    id="problem_comment" 
                    name="problem_comment" 
                    rows="5" 
                    required
                    placeholder="例：受験者の主訴を確認する質問から始められており、話の流れが良かったです。キャリアの経歴についても丁寧に聞けています。ただし、表面的な悩みから本質的な課題を掘り下げる質問をもう少し意識すると良いでしょう。「なぜそう感じたのですか？」といった深掘りの質問を増やしてみてください。"
                  ><?php echo $is_edit_mode ? h($feedback_data['problem_comment'] ?? '') : ''; ?></textarea>
                </div>
              </div>

              <!-- セクション3：具体的展開 -->
              <div class="form-section">
                <h3 class="section-title">
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
                
                <div class="form-group">
                  <label for="development_comment">具体的なフィードバック <span class="required">*</span></label>
                  <textarea 
                    id="development_comment" 
                    name="development_comment" 
                    rows="5" 
                    required
                    placeholder="例：受験者の目指す方向性を引き出そうとする質問ができていました。自己理解を促すための質問も良いですが、さらに具体的なアクションプランに落とし込む支援まで持っていけるとより良いでしょう。「次の一歩として何ができそうですか？」といった質問で、受験者自身に考えさせる支援を意識してみてください。"
                  ><?php echo $is_edit_mode ? h($feedback_data['development_comment'] ?? '') : ''; ?></textarea>
                </div>
              </div>

              <!-- 次回へのアドバイス -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="lightbulb"></i>
                  次回の面談に向けたアドバイス
                </h3>
                <p class="section-description">次回の練習で特に意識してほしいポイントや励ましのメッセージを記入してください</p>
                
                <div class="form-group">
                  <label for="next_advice">アドバイス <span class="required">*</span></label>
                  <textarea 
                    id="next_advice" 
                    name="next_advice" 
                    rows="5" 
                    required
                    placeholder="例：全体として基本的な姿勢はしっかりできています。次回は「問題把握」の深掘りと「具体的展開」での支援の部分を特に意識して練習してみましょう。時間配分も意識して、前半15分で問題把握、後半15分で具体的展開という流れを作ってみてください。着実に成長していますので、この調子で頑張りましょう！"
                  ><?php echo $is_edit_mode ? h($feedback_data['next_advice'] ?? '') : ''; ?></textarea>
                </div>
              </div>

              <!-- 送信ボタン -->
              <div class="form-actions">
                <button type="submit" class="btn-primary btn-large">
                  <i data-lucide="send"></i>
                  <?php echo $is_edit_mode ? 'フィードバックを更新' : 'フィードバックを提出'; ?>
                </button>
              </div>

            </form>
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
