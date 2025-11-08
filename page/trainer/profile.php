<?php
/**
 * トレーナー プロフィール表示・編集ページ
 * 
 * @package CareerTre
 */

// 認証チェック
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/database.php';
requireLogin('trainer');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$trainer_id = $current_user['id'];

// エラーメッセージ・成功メッセージ取得
$error = getSessionMessage('error');
$errors = getSessionMessage('errors');
$success = getSessionMessage('success');

// データベース接続
$pdo = getDBConnection();

// トレーナー情報を取得
$stmt = $pdo->prepare("
    SELECT 
        id,
        name,
        nickname,
        email,
        career_description,
        available_time
    FROM trainers 
    WHERE id = ?
");
$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    redirect('/gs_code/gga/page/trainer/mypage.php');
    exit;
}

// プロフィールの入力状況チェック
$profile_complete = !empty($trainer['nickname']) && 
                   !empty($trainer['career_description']) && 
                   !empty($trainer['available_time']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>プロフィール - CareerTre キャリトレ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../assets/css/variables.css">
  <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body>
  <!-- メインコンテナ -->
  <main class="hero-container">
    <div class="container-narrow">
      
      <!-- ヘッダー -->
      <header class="page-header fade-in">
        <a href="mypage.php" class="back-link">
          <i data-lucide="arrow-left"></i>
          マイページに戻る
        </a>
        <h1 class="logo-primary">CareerTre</h1>
        <p class="hero-tagline">-キャリトレ-</p>
      </header>

      <!-- プロフィールカード -->
      <section class="content-section fade-in">
        <article class="card">
          <div class="card-header">
            <h2>
              <i data-lucide="user"></i>
              プロフィール
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

            <?php if (!$profile_complete): ?>
            <div class="alert alert-info">
              <i data-lucide="info"></i>
              <span>プロフィールを入力すると、受験者があなたを選びやすくなります。</span>
            </div>
            <?php endif; ?>

            <form action="profile_update.php" method="POST" class="profile-form">
              
              <!-- 本名（表示のみ） -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="id-card"></i>
                  基本情報
                </h3>
                
                <div class="form-group">
                  <label for="name">本名（登録名）</label>
                  <input 
                    type="text" 
                    id="name" 
                    value="<?php echo h($trainer['name']); ?>" 
                    disabled
                    style="background-color: #f5f5f5; cursor: not-allowed;"
                  >
                  <small class="form-text">※本名は変更できません</small>
                </div>

                <div class="form-group">
                  <label for="email">メールアドレス</label>
                  <input 
                    type="email" 
                    id="email" 
                    value="<?php echo h($trainer['email']); ?>" 
                    disabled
                    style="background-color: #f5f5f5; cursor: not-allowed;"
                  >
                  <small class="form-text">※メールアドレスは変更できません</small>
                </div>
              </div>

              <!-- ニックネーム -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="smile"></i>
                  表示名（ニックネーム）
                </h3>
                <p class="section-description">
                  受験者に表示される名前です。本名でもイニシャルでもニックネームでもOKです。
                </p>
                
                <div class="form-group">
                  <label for="nickname">ニックネーム <span class="required">*</span></label>
                  <input 
                    type="text" 
                    id="nickname" 
                    name="nickname" 
                    value="<?php echo h($trainer['nickname'] ?? ''); ?>" 
                    placeholder="例：田中先生、T.Tanaka、キャリアサポーター田中"
                    required
                    maxlength="100"
                  >
                  <small class="form-text">
                    <i data-lucide="info"></i>
                    本名を使いたくない場合は、イニシャルやニックネームをご使用ください
                  </small>
                </div>
              </div>

              <!-- 経歴 -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="briefcase"></i>
                  経歴・専門分野
                </h3>
                <p class="section-description">
                  あなたの経歴や専門分野を記載してください。社名は記載しなくてもOKです（業界や職種のみでも可）。
                </p>
                
                <div class="form-group">
                  <label for="career_description">経歴 <span class="required">*</span></label>
                  <textarea 
                    id="career_description" 
                    name="career_description" 
                    rows="8" 
                    required
                    placeholder="例：
・IT業界で15年のキャリア（SE→プロジェクトマネージャー→人事部）
・大手メーカーで新卒採用や人材育成を担当
・キャリアコンサルタント資格取得後、フリーランスとして活動
・専門分野：IT業界への転職支援、キャリアチェンジ支援"
                  ><?php echo h($trainer['career_description'] ?? ''); ?></textarea>
                  <small class="form-text">
                    <i data-lucide="info"></i>
                    社名を書きたい場合は書いてもOKです。業界・職種・専門分野などを自由に記載してください。
                  </small>
                </div>
              </div>

              <!-- 都合の良い時間 -->
              <div class="form-section">
                <h3 class="section-title">
                  <i data-lucide="clock"></i>
                  対応可能な時間帯
                </h3>
                <p class="section-description">
                  面談対応が可能な時間帯を記載してください。受験者が予約リクエストしやすくなります。
                </p>
                
                <div class="form-group">
                  <label for="available_time">対応可能時間 <span class="required">*</span></label>
                  <textarea 
                    id="available_time" 
                    name="available_time" 
                    rows="6" 
                    required
                    placeholder="例：
・平日：19:00〜22:00
・土日：10:00〜18:00
・基本的に夜間と週末が対応可能です
・事前にご相談いただければ、平日昼間も調整可能です"
                  ><?php echo h($trainer['available_time'] ?? ''); ?></textarea>
                  <small class="form-text">
                    <i data-lucide="info"></i>
                    曜日・時間帯を具体的に記載すると、受験者が予約しやすくなります
                  </small>
                </div>
              </div>

              <!-- 送信ボタン -->
              <div class="form-actions">
                <button type="submit" class="btn-primary btn-large">
                  <i data-lucide="save"></i>
                  プロフィールを保存
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
