<?php
// セッション開始（実際の実装では認証チェックを行う）
session_start();

// 予約IDを取得
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ダミーデータ（実際はデータベースから取得）
$user_name = "山田 太郎";

// 予約詳細データ
$reservation = [
    'id' => $reservation_id,
    'date' => '2025-11-15',
    'time' => '14:00',
    'end_time' => '15:00',
    'consultant_name' => '佐藤 花子',
    'status' => 'completed'
];

// 既存の自己フィードバック（編集時）
$existing_feedback = null;
// $existing_feedback = [
//     'satisfaction' => 4,
//     'strengths' => '傾聴姿勢を意識できた',
//     'challenges' => '質問の組み立てが難しかった',
//     'learnings' => '相手の話を最後まで聞くことの重要性',
//     'next_goals' => '質問力の向上'
// ];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>自己フィードバック入力 - CareerTre キャリトレ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../../../../assets/css/variables.css">
  <link rel="stylesheet" href="../../../../../assets/css/custom.css">
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
          <a href="../../../logout.php" class="nav-link">
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
        <a href="../detail.php?id=<?php echo $reservation_id; ?>" class="back-link">
          <i data-lucide="arrow-left"></i>
          予約詳細に戻る
        </a>
        <h2 class="page-title">自己フィードバック入力</h2>
        <p class="page-description">練習を振り返り、自己評価を記録しましょう</p>
      </header>

      <!-- 予約情報バナー -->
      <section class="info-banner fade-in">
        <div class="banner-content">
          <div class="banner-icon">
            <i data-lucide="calendar"></i>
          </div>
          <div class="banner-text">
            <h3>練習日時</h3>
            <p>
              <?php 
                $date = new DateTime($reservation['date']);
                $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                echo $date->format('Y年n月j日') . '（' . $weekdays[$date->format('w')] . '）';
              ?>
              <?php echo $reservation['time']; ?> - <?php echo $reservation['end_time']; ?>
            </p>
          </div>
          <div class="banner-text">
            <h3>担当コンサルタント</h3>
            <p><?php echo htmlspecialchars($reservation['consultant_name']); ?></p>
          </div>
        </div>
      </section>

      <!-- フィードバックフォーム -->
      <section class="feedback-form-section fade-in">
        <article class="form-card">
          <form action="feedback_process.php" method="POST" class="feedback-form">
            <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">

            <!-- 満足度 -->
            <div class="form-group">
              <label class="form-label">
                <i data-lucide="star"></i>
                今回の練習の満足度
                <span class="required">*</span>
              </label>
              <div class="rating-group">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <input 
                    type="radio" 
                    id="satisfaction-<?php echo $i; ?>" 
                    name="satisfaction" 
                    value="<?php echo $i; ?>"
                    <?php echo ($existing_feedback && $existing_feedback['satisfaction'] == $i) ? 'checked' : ''; ?>
                    required
                  >
                  <label for="satisfaction-<?php echo $i; ?>" class="rating-label">
                    <i data-lucide="star"></i>
                    <span><?php echo $i; ?></span>
                  </label>
                <?php endfor; ?>
              </div>
              <small class="form-help">5段階で評価してください（5が最高）</small>
            </div>

            <!-- できたこと・良かった点 -->
            <div class="form-group">
              <label for="strengths" class="form-label">
                <i data-lucide="thumbs-up"></i>
                できたこと・良かった点
                <span class="required">*</span>
              </label>
              <textarea 
                id="strengths" 
                name="strengths" 
                rows="4" 
                placeholder="例：クライアントの話を最後まで丁寧に聞くことができた。うなずきやあいづちのタイミングが良かった。"
                required
              ><?php echo $existing_feedback ? htmlspecialchars($existing_feedback['strengths']) : ''; ?></textarea>
              <small class="form-help">今回の練習で上手くできたことを記入してください</small>
            </div>

            <!-- 難しかったこと・課題 -->
            <div class="form-group">
              <label for="challenges" class="form-label">
                <i data-lucide="alert-circle"></i>
                難しかったこと・課題
                <span class="required">*</span>
              </label>
              <textarea 
                id="challenges" 
                name="challenges" 
                rows="4" 
                placeholder="例：質問の組み立てが難しく、的確な質問ができなかった。時間配分が上手くいかなかった。"
                required
              ><?php echo $existing_feedback ? htmlspecialchars($existing_feedback['challenges']) : ''; ?></textarea>
              <small class="form-help">うまくできなかったことや難しかったことを記入してください</small>
            </div>

            <!-- 学んだこと・気づき -->
            <div class="form-group">
              <label for="learnings" class="form-label">
                <i data-lucide="lightbulb"></i>
                学んだこと・気づき
                <span class="required">*</span>
              </label>
              <textarea 
                id="learnings" 
                name="learnings" 
                rows="4" 
                placeholder="例：相手の感情に寄り添うことの大切さを実感した。質問する前に相手の話を整理する必要があると気づいた。"
                required
              ><?php echo $existing_feedback ? htmlspecialchars($existing_feedback['learnings']) : ''; ?></textarea>
              <small class="form-help">今回の練習を通じて学んだことや新しい気づきを記入してください</small>
            </div>

            <!-- 次回に向けた目標 -->
            <div class="form-group">
              <label for="next_goals" class="form-label">
                <i data-lucide="target"></i>
                次回に向けた目標
                <span class="required">*</span>
              </label>
              <textarea 
                id="next_goals" 
                name="next_goals" 
                rows="4" 
                placeholder="例：質問力を向上させる。開かれた質問と閉ざされた質問を使い分ける。時間配分を意識する。"
                required
              ><?php echo $existing_feedback ? htmlspecialchars($existing_feedback['next_goals']) : ''; ?></textarea>
              <small class="form-help">次回の練習で意識したい目標を記入してください</small>
            </div>

            <!-- 送信ボタン -->
            <div class="form-actions">
              <a href="../detail.php?id=<?php echo $reservation_id; ?>" class="btn-secondary">
                <i data-lucide="x"></i>
                キャンセル
              </a>
              <button type="submit" class="btn-primary btn-large">
                <i data-lucide="save"></i>
                <?php echo $existing_feedback ? '更新する' : '保存する'; ?>
              </button>
            </div>
          </form>
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

    // 星評価のインタラクティブ表示
    document.querySelectorAll('.rating-label').forEach(label => {
      label.addEventListener('mouseenter', function() {
        const star = this.querySelector('i');
        if (star) {
          star.setAttribute('fill', 'currentColor');
        }
      });
      
      label.addEventListener('mouseleave', function() {
        const radio = document.getElementById(this.getAttribute('for'));
        if (!radio.checked) {
          const star = this.querySelector('i');
          if (star) {
            star.removeAttribute('fill');
          }
        }
      });
    });

    // 選択された星を塗りつぶす
    document.querySelectorAll('input[name="satisfaction"]').forEach(radio => {
      radio.addEventListener('change', function() {
        document.querySelectorAll('.rating-label i').forEach(star => {
          star.removeAttribute('fill');
        });
        
        const selectedValue = parseInt(this.value);
        for (let i = 5; i >= selectedValue; i--) {
          const label = document.querySelector(`label[for="satisfaction-${i}"] i`);
          if (label) {
            label.setAttribute('fill', 'currentColor');
          }
        }
      });
      
      // 初期表示
      if (radio.checked) {
        const selectedValue = parseInt(radio.value);
        for (let i = 5; i >= selectedValue; i--) {
          const label = document.querySelector(`label[for="satisfaction-${i}"] i`);
          if (label) {
            label.setAttribute('fill', 'currentColor');
          }
        }
      }
    });
  </script>
</body>
</html>
