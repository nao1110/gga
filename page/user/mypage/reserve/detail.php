<?php
// セッション開始（実際の実装では認証チェックを行う）
session_start();

// 予約IDを取得
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ダミーデータ（実際はデータベースから取得）
$user_name = "山田 太郎";

// ペルソナ情報（5つのケース）
$personas = [
    1 => [
        'title' => 'ペルソナ1：大手IT企業の営業職（入社2年目）',
        'industry' => '大手IT企業の営業職（入社2年目）',
        'theme' => '大学新卒/入社後のリアリティショック、適性の不一致',
        'quote' => '「この会社で自分のキャリアパスが見えず、テレアポばかりで成長できない。早く転職すべきか悩んでいます。」',
        'context' => '若手社員の離職率の高さ、転職の一般化、キャリア形成への意識の高まり。'
    ],
    2 => [
        'title' => 'ペルソナ2：中堅メーカーの経理・財務担当（課長代理）',
        'industry' => '中堅メーカーの経理・財務担当（課長代理）',
        'theme' => 'ライフイベント：介護と仕事の両立',
        'quote' => '「仕事も介護も中途半端で、心身の疲労が限界です。キャリアを断念して介護に専念すべきでしょうか。」',
        'context' => '高齢化社会の進展、介護離職の増加、ダブルケアによる心身の不調。'
    ],
    3 => [
        'title' => 'ペルソナ3：大手食品メーカーの契約社員（研究開発部門、勤続8年）',
        'industry' => '大手食品メーカーの契約社員（研究開発部門、勤続8年）',
        'theme' => '雇用形態：非正規社員の不安定な立場',
        'quote' => '「正社員と同等の貢献をしているのに契約社員のままで、将来の結婚や生活の不安定さに限界を感じています。」',
        'context' => '雇用の多様化、非正規雇用の増加、将来的なライフイベントへの影響。'
    ],
    4 => [
        'title' => 'ペルソナ4：総合商社の管理部門（人事・総務、部長職）',
        'industry' => '総合商社の管理部門（人事・総務、部長職）',
        'theme' => '組織再編：早期退職制度の利用検討',
        'quote' => '「上乗せがあるうちに退職すべきか、残るべきか。50代で新しい仕事が見つかるのかが一番不安です。」',
        'context' => '事業構造転換、自身の市場価値への不安、急な意思決定の必要性。'
    ],
    5 => [
        'title' => 'ペルソナ5：精密機械メーカーの製造部門（定年後、再雇用契約社員）',
        'industry' => '精密機械メーカーの製造部門（定年後、再雇用契約社員）',
        'theme' => 'シニア層：定年後・再雇用での不適応',
        'quote' => '「給与が安く、後輩のサポートばかりでやりがいを感じられない。このまましがみつくべきか迷っています。」',
        'context' => '役割喪失によるアイデンティティの危機、再雇用後のモチベーション維持、高齢者の戦力化の難しさ。'
    ]
];

// 予約詳細データ
$reservation = [
    'id' => $reservation_id,
    'date' => '2025-10-20',
    'time' => '14:00',
    'end_time' => '15:00',
    'consultant_name' => '佐藤 花子',
    'consultant_id' => 101,
    'consultant_specialty' => 'キャリアチェンジ支援、自己分析',
    'status' => 'completed', // completed, confirmed, pending, cancelled
    'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    'created_at' => '2025-10-15 10:30:00',
    'confirmed_at' => '2025-10-16 15:20:00',
    'completed_at' => '2025-10-20 15:05:00',
    'persona_number' => 3, // 1から5までのペルソナ番号
    
    // フィードバック
    'feedback' => [
        'overall_score' => 85,
        'relationship_score' => 88,
        'listening_score' => 90,
        'questioning_score' => 82,
        'summary_score' => 80,
        'strengths' => [
            '傾聴姿勢が非常に良好で、相手の話をしっかり受け止めている',
            'うなずきやあいづちのタイミングが適切',
            '共感的な言葉かけができている'
        ],
        'improvements' => [
            '質問の組み立てをもう少し整理すると良い',
            'クライアントの本質的な課題に迫る深い質問を意識する',
            '時間配分に注意（前半に時間を使いすぎている）'
        ],
        'next_goals' => [
            'キャリアの方向性を引き出す質問力の向上',
            '具体的なアクションプランへの導き方',
            '限られた時間での効果的な面談構成'
        ],
        'comment' => '全体として良い面談ができていました。特に傾聴姿勢は素晴らしく、クライアントも安心して話せる雰囲気を作れていました。次回は質問の構成と時間配分を意識して練習してみましょう。確実に成長しています！'
    ],
    
    // 録画
    'recording' => [
        'available' => true,
        'url' => 'https://example.com/recordings/session-12345.mp4',
        'duration' => '58:32',
        'file_size' => '1.2 GB',
        'thumbnail' => null
    ],
    
    // 自己フィードバック
    'self_feedback' => [
        'satisfaction' => 4,
        'strengths' => '傾聴姿勢を意識して、最後まで相手の話を聞くことができた。うなずきやあいづちのタイミングも良かったと思う。',
        'challenges' => '質問の組み立てが難しく、的確な質問ができなかった。特に相手の本質的な課題を引き出す深い質問が足りなかった。',
        'learnings' => '相手の感情に寄り添うことの大切さを実感した。質問する前に相手の話を整理する必要があると気づいた。',
        'next_goals' => '質問力を向上させる。開かれた質問と閉ざされた質問を使い分ける。時間配分を意識する。'
    ]
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>予約詳細 - CareerTre キャリトレ</title>
  
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
          <span class="navbar-tagline">-キャリトレ-</span>
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
                      $date = new DateTime($reservation['date']);
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
                    <?php echo $reservation['time']; ?> - <?php echo $reservation['end_time']; ?>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-label">
                    <i data-lucide="user"></i>
                    担当コンサルタント
                  </div>
                  <div class="info-value">
                    <?php echo htmlspecialchars($reservation['consultant_name']); ?>
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
          <?php if ($reservation['status'] === 'completed' && isset($personas[$reservation['persona_number']])): ?>
            <?php $persona = $personas[$reservation['persona_number']]; ?>
          <article class="detail-card fade-in">
            <div class="detail-card-header">
              <h3>
                <i data-lucide="user-circle"></i>
                <?php echo htmlspecialchars($persona['title']); ?>
              </h3>
            </div>
            <div class="detail-card-body">
              <div class="persona-info">
                <div class="persona-item">
                  <div class="persona-label">
                    <i data-lucide="briefcase"></i>
                    業種・職種
                  </div>
                  <div class="persona-value">
                    <?php echo htmlspecialchars($persona['industry']); ?>
                  </div>
                </div>

                <div class="persona-item">
                  <div class="persona-label">
                    <i data-lucide="target"></i>
                    相談の核となるテーマ
                  </div>
                  <div class="persona-value">
                    <?php echo htmlspecialchars($persona['theme']); ?>
                  </div>
                </div>

                <div class="persona-item">
                  <div class="persona-label">
                    <i data-lucide="message-square"></i>
                    相談の核となる一言
                  </div>
                  <div class="persona-quote">
                    <?php echo htmlspecialchars($persona['quote']); ?>
                  </div>
                </div>

                <div class="persona-item">
                  <div class="persona-label">
                    <i data-lucide="alert-circle"></i>
                    関連する危機感・社会現象
                  </div>
                  <div class="persona-value">
                    <?php echo htmlspecialchars($persona['context']); ?>
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
                <?php echo isset($reservation['self_feedback']) ? '編集' : '入力'; ?>
              </a>
            </div>
            <div class="detail-card-body">
              <?php if (isset($reservation['self_feedback'])): ?>
                <div class="self-feedback-content">
                  <!-- 満足度 -->
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="star"></i>
                      満足度
                    </h4>
                    <div class="satisfaction-stars">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i data-lucide="star" <?php echo $i <= $reservation['self_feedback']['satisfaction'] ? 'fill="currentColor"' : ''; ?> style="color: var(--primary);"></i>
                      <?php endfor; ?>
                      <span class="satisfaction-text"><?php echo $reservation['self_feedback']['satisfaction']; ?> / 5</span>
                    </div>
                  </div>

                  <!-- できたこと・良かった点 -->
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="thumbs-up"></i>
                      できたこと・良かった点
                    </h4>
                    <div class="feedback-text">
                      <?php echo nl2br(htmlspecialchars($reservation['self_feedback']['strengths'])); ?>
                    </div>
                  </div>

                  <!-- 難しかったこと・課題 -->
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="alert-circle"></i>
                      難しかったこと・課題
                    </h4>
                    <div class="feedback-text">
                      <?php echo nl2br(htmlspecialchars($reservation['self_feedback']['challenges'])); ?>
                    </div>
                  </div>

                  <!-- 学んだこと・気づき -->
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="lightbulb"></i>
                      学んだこと・気づき
                    </h4>
                    <div class="feedback-text">
                      <?php echo nl2br(htmlspecialchars($reservation['self_feedback']['learnings'])); ?>
                    </div>
                  </div>

                  <!-- 次回に向けた目標 -->
                  <div class="feedback-section">
                    <h4 class="feedback-title">
                      <i data-lucide="target"></i>
                      次回に向けた目標
                    </h4>
                    <div class="feedback-text">
                      <?php echo nl2br(htmlspecialchars($reservation['self_feedback']['next_goals'])); ?>
                    </div>
                  </div>
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
          <?php if ($reservation['status'] === 'completed' && isset($reservation['feedback'])): ?>
          <article class="detail-card fade-in">
            <div class="detail-card-header">
              <h3>
                <i data-lucide="message-square"></i>
                キャリアコンサルタントからのフィードバック
              </h3>
            </div>
            <div class="detail-card-body">
              
              <!-- 良かった点 -->
              <div class="feedback-section">
                <h4 class="feedback-title">
                  <i data-lucide="thumbs-up"></i>
                  良かった点
                </h4>
                <ul class="feedback-list success">
                  <?php foreach ($reservation['feedback']['strengths'] as $strength): ?>
                    <li>
                      <i data-lucide="check"></i>
                      <?php echo htmlspecialchars($strength); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <!-- 改善点 -->
              <div class="feedback-section">
                <h4 class="feedback-title">
                  <i data-lucide="alert-circle"></i>
                  改善点
                </h4>
                <ul class="feedback-list warning">
                  <?php foreach ($reservation['feedback']['improvements'] as $improvement): ?>
                    <li>
                      <i data-lucide="arrow-right"></i>
                      <?php echo htmlspecialchars($improvement); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <!-- 次回の目標 -->
              <div class="feedback-section">
                <h4 class="feedback-title">
                  <i data-lucide="target"></i>
                  次回の目標
                </h4>
                <ul class="feedback-list info">
                  <?php foreach ($reservation['feedback']['next_goals'] as $goal): ?>
                    <li>
                      <i data-lucide="flag"></i>
                      <?php echo htmlspecialchars($goal); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <!-- コメント -->
              <div class="feedback-comment">
                <h4 class="feedback-title">
                  <i data-lucide="message-circle"></i>
                  コンサルタントからのコメント
                </h4>
                <div class="comment-box">
                  <p><?php echo nl2br(htmlspecialchars($reservation['feedback']['comment'])); ?></p>
                  <div class="comment-author">
                    <i data-lucide="user"></i>
                    <?php echo htmlspecialchars($reservation['consultant_name']); ?>
                  </div>
                </div>
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
