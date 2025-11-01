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

// 予約IDを取得
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reservation_id) {
    redirect('/page/trainer/mypage.php');
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
        u.name as user_name,
        u.email as user_email,
        p.persona_name,
        f.id as feedback_id,
        f.comment as feedback_comment,
        f.created_at as feedback_date
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
    redirect('/page/trainer/mypage.php');
    exit;
}

// フィードバックデータの取得と解析
$is_edit_mode = false;
$feedback_data = null;

if ($reservation['feedback_id'] && $reservation['feedback_comment']) {
    $is_edit_mode = true;
    $feedback_json = json_decode($reservation['feedback_comment'], true);
    
    if ($feedback_json) {
        $feedback_data = [
            'strengths' => $feedback_json['strengths'] ?? [],
            'improvements' => $feedback_json['improvements'] ?? [],
            'next_goals' => $feedback_json['next_goals'] ?? [],
            'comment' => $feedback_json['overall_comment'] ?? $feedback_json['comment'] ?? '',
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

              <div class="info-item">
                <div class="info-label">
                  <i data-lucide="briefcase"></i>
                  ペルソナ
                </div>
                <div class="info-value">
                  <?php echo h($reservation['persona_name']); ?>
                </div>
              </div>
            </div>
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
            <?php if ($is_edit_mode): ?>
            <div class="alert alert-info">
              <i data-lucide="info"></i>
              <span>提出済みのフィードバックです。編集して再提出することができます。</span>
            </div>
            <?php endif; ?>

            <form action="feedback_process.php" method="POST" class="feedback-form">
              <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
              
              <!-- 良かった点 -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="thumbs-up"></i>
                  良かった点
                </h3>
                <p class="section-description">受験者が特に優れていた点を3つ以上記入してください</p>
                
                <div class="form-group">
                  <label for="strength1">良かった点 1 <span class="required">*</span></label>
                  <textarea 
                    id="strength1" 
                    name="strengths[]" 
                    rows="2" 
                    required
                    placeholder="例：傾聴姿勢が非常に良好で、相手の話をしっかり受け止めている"
                  ><?php echo $is_edit_mode ? h($feedback_data['strengths'][0] ?? '') : ''; ?></textarea>
                </div>

                <div class="form-group">
                  <label for="strength2">良かった点 2 <span class="required">*</span></label>
                  <textarea 
                    id="strength2" 
                    name="strengths[]" 
                    rows="2" 
                    required
                    placeholder="例：うなずきやあいづちのタイミングが適切"
                  ><?php echo $is_edit_mode ? h($feedback_data['strengths'][1] ?? '') : ''; ?></textarea>
                </div>

                <div class="form-group">
                  <label for="strength3">良かった点 3 <span class="required">*</span></label>
                  <textarea 
                    id="strength3" 
                    name="strengths[]" 
                    rows="2" 
                    required
                    placeholder="例：共感的な言葉かけができている"
                  ><?php echo $is_edit_mode ? h($feedback_data['strengths'][2] ?? '') : ''; ?></textarea>
                </div>
              </div>

              <!-- 改善点 -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="alert-circle"></i>
                  改善すると良い点
                </h3>
                <p class="section-description">次回の練習で意識すべき改善点を3つ以上記入してください</p>
                
                <div class="form-group">
                  <label for="improvement1">改善点 1 <span class="required">*</span></label>
                  <textarea 
                    id="improvement1" 
                    name="improvements[]" 
                    rows="2" 
                    required
                    placeholder="例：質問の組み立てをもう少し整理すると良い"
                  ><?php echo $is_edit_mode ? h($feedback_data['improvements'][0] ?? '') : ''; ?></textarea>
                </div>

                <div class="form-group">
                  <label for="improvement2">改善点 2 <span class="required">*</span></label>
                  <textarea 
                    id="improvement2" 
                    name="improvements[]" 
                    rows="2" 
                    required
                    placeholder="例：クライアントの本質的な課題に迫る深い質問を意識する"
                  ><?php echo $is_edit_mode ? h($feedback_data['improvements'][1] ?? '') : ''; ?></textarea>
                </div>

                <div class="form-group">
                  <label for="improvement3">改善点 3 <span class="required">*</span></label>
                  <textarea 
                    id="improvement3" 
                    name="improvements[]" 
                    rows="2" 
                    required
                    placeholder="例：時間配分に注意（前半に時間を使いすぎている）"
                  ><?php echo $is_edit_mode ? h($feedback_data['improvements'][2] ?? '') : ''; ?></textarea>
                </div>
              </div>

              <!-- 次回の目標 -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="target"></i>
                  次回の目標・アドバイス
                </h3>
                <p class="section-description">次回の練習で取り組むべき目標を3つ以上記入してください</p>
                
                <div class="form-group">
                  <label for="goal1">目標 1 <span class="required">*</span></label>
                  <textarea 
                    id="goal1" 
                    name="next_goals[]" 
                    rows="2" 
                    required
                    placeholder="例：キャリアの方向性を引き出す質問力の向上"
                  ><?php echo $is_edit_mode ? h($feedback_data['next_goals'][0] ?? '') : ''; ?></textarea>
                </div>

                <div class="form-group">
                  <label for="goal2">目標 2 <span class="required">*</span></label>
                  <textarea 
                    id="goal2" 
                    name="next_goals[]" 
                    rows="2" 
                    required
                    placeholder="例：具体的なアクションプランへの導き方"
                  ><?php echo $is_edit_mode ? h($feedback_data['next_goals'][1] ?? '') : ''; ?></textarea>
                </div>

                <div class="form-group">
                  <label for="goal3">目標 3 <span class="required">*</span></label>
                  <textarea 
                    id="goal3" 
                    name="next_goals[]" 
                    rows="2" 
                    required
                    placeholder="例：限られた時間での効果的な面談構成"
                  ><?php echo $is_edit_mode ? h($feedback_data['next_goals'][2] ?? '') : ''; ?></textarea>
                </div>
              </div>

              <!-- 総評コメント -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="message-square"></i>
                  総評コメント
                </h3>
                <p class="section-description">全体的な印象や励ましのメッセージを記入してください</p>
                
                <div class="form-group">
                  <label for="comment">総評 <span class="required">*</span></label>
                  <textarea 
                    id="comment" 
                    name="comment" 
                    rows="6" 
                    required
                    placeholder="例：全体として良い面談ができていました。特に傾聴姿勢は素晴らしく、クライアントも安心して話せる雰囲気を作れていました。次回は質問の構成と時間配分を意識して練習してみましょう。確実に成長しています！"
                  ><?php echo $is_edit_mode ? h($feedback_data['comment'] ?? '') : ''; ?></textarea>
                </div>
              </div>

              <!-- 送信ボタン -->
              <div class="form-actions">
                <a href="../../mypage.php" class="btn-secondary">
                  キャンセル
                </a>
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
