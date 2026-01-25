<?php
// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

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

// エラーメッセージ・成功メッセージ取得
$error = getSessionMessage('error');
$errors = getSessionMessage('errors');
$success = getSessionMessage('success');
$old_trainer_id = getSessionMessage('old_trainer_id');
$old_persona_id = getSessionMessage('old_persona_id');
$old_meeting_date = getSessionMessage('old_meeting_date');

// データベース接続
$db = getDBConnection();

// 予約総数をチェック
$stmt = $db->prepare("
    SELECT COUNT(*) as total_count FROM reserves 
    WHERE user_id = ?
    AND status IN ('pending', 'confirmed', 'completed')
");
$stmt->execute([$user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_reservations = $result['total_count'];
$max_reservations = 3;
$can_reserve = $total_reservations < $max_reservations;

// トレーナー一覧取得
$stmt = $db->prepare("
    SELECT id, name, email
    FROM trainers
    WHERE is_active = 1
    ORDER BY name ASC
");
$stmt->execute();
$trainers = $stmt->fetchAll();

// ペルソナ一覧取得
$stmt = $db->prepare("
    SELECT id, persona_name, age, job
    FROM personas
    ORDER BY id ASC
");
$stmt->execute();
$personas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>新規予約 - キャリアトレーナーズ</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../../../../assets/css/variables.css?v=2.1">
  <link rel="stylesheet" href="../../../../assets/css/custom.css?v=2.1">
  
  <style>
    body {
      background: linear-gradient(135deg, #FFF8E1 0%, #FFFFFF 100%);
      min-height: 100vh;
    }
    
    .reserve-header {
      background: white;
      padding: 25px 0;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 40px;
    }
    
    .header-content {
      max-width: 900px;
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
    
    .btn-back {
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
    
    .btn-back:hover {
      background: #FF9800;
      color: white;
    }
    
    .container-reserve {
      max-width: 900px;
      margin: 0 auto;
      padding: 0 20px 40px;
    }
    
    .info-section {
      background: white;
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .info-title {
      font-size: 1.3rem;
      font-weight: 700;
      color: #333;
      margin: 0 0 20px 0;
      padding-bottom: 15px;
      border-bottom: 3px solid #FFE0B2;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .exam-info-box {
      background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
      border-left: 5px solid #FF9800;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    
    .exam-info-box h3 {
      color: #E65100;
      font-size: 1.1rem;
      font-weight: 700;
      margin: 0 0 15px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .exam-dates {
      background: white;
      padding: 15px;
      border-radius: 8px;
      margin: 10px 0;
      font-weight: 600;
      color: #E65100;
    }
    
    .time-breakdown {
      background: #E8F5E9;
      padding: 20px;
      border-radius: 10px;
      border-left: 5px solid #4CAF50;
      margin-top: 15px;
    }
    
    .time-breakdown h4 {
      color: #2E7D32;
      font-size: 1rem;
      font-weight: 700;
      margin: 0 0 15px 0;
    }
    
    .time-breakdown ul {
      margin: 0;
      padding-left: 20px;
      color: #333;
      line-height: 1.8;
    }
    
    .time-breakdown ul li {
      margin-bottom: 8px;
    }
    
    .time-breakdown .total-time {
      background: #2E7D32;
      color: white;
      padding: 12px;
      border-radius: 8px;
      margin-top: 15px;
      text-align: center;
      font-weight: 700;
      font-size: 1.1rem;
    }
    
    .availability-note {
      background: #E3F2FD;
      border-left: 5px solid #2196F3;
      padding: 15px;
      border-radius: 10px;
      color: #0D47A1;
      font-size: 0.95rem;
      line-height: 1.8;
    }
    
    .form-card {
      background: white;
      border-radius: 15px;
      padding: 40px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .step-indicator {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 40px;
      position: relative;
    }
    
    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      position: relative;
      z-index: 2;
    }
    
    .step-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: #E0E0E0;
      color: #666;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      font-size: 1.3rem;
      transition: all 0.3s;
    }
    
    .step.active .step-circle {
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: white;
      box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
    }
    
    .step.completed .step-circle {
      background: #4CAF50;
      color: white;
    }
    
    .step-label {
      font-size: 0.9rem;
      font-weight: 600;
      color: #666;
    }
    
    .step.active .step-label {
      color: #FF9800;
    }
    
    .step-line {
      flex: 1;
      height: 4px;
      background: #E0E0E0;
      margin: 0 10px;
      position: relative;
      top: -20px;
    }
    
    .form-step {
      display: none;
    }
    
    .form-step.active {
      display: block;
      animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .step-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #333;
      margin: 0 0 25px 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .calendar-container {
      background: #FAFAFA;
      padding: 20px;
      border-radius: 12px;
      margin: 20px 0;
    }
    
    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .calendar-title {
      font-size: 1.2rem;
      font-weight: 700;
      color: #333;
    }
    
    .calendar-nav {
      background: white;
      border: 2px solid #FF9800;
      color: #FF9800;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .calendar-nav:hover {
      background: #FF9800;
      color: white;
    }
    
    .calendar-weekdays {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 5px;
      margin-bottom: 10px;
    }
    
    .weekday {
      text-align: center;
      font-weight: 600;
      color: #666;
      padding: 10px;
      font-size: 0.9rem;
    }
    
    .calendar-days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 5px;
    }
    
    .calendar-day {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background: white;
      border: 2px solid #E0E0E0;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .calendar-day:hover:not(.disabled):not(.empty) {
      border-color: #FF9800;
      background: #FFF3E0;
    }
    
    .calendar-day.selected {
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: white;
      border-color: #F57C00;
    }
    
    .calendar-day.disabled {
      background: #F5F5F5;
      color: #BDBDBD;
      cursor: not-allowed;
      border-color: transparent;
    }
    
    .calendar-day.empty {
      background: transparent;
      border: none;
      cursor: default;
    }
    
    .time-slots {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      gap: 15px;
      margin: 20px 0;
    }
    
    .time-slot {
      padding: 15px;
      background: white;
      border: 2px solid #E0E0E0;
      border-radius: 8px;
      text-align: center;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .time-slot:hover {
      border-color: #FF9800;
      background: #FFF3E0;
    }
    
    .time-slot.selected {
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: white;
      border-color: #F57C00;
    }
    
    .selected-info {
      background: #E3F2FD;
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
      display: flex;
      align-items: center;
      gap: 10px;
      color: #1565C0;
      font-weight: 600;
    }
    
    .confirmation-section {
      background: #FAFAFA;
      padding: 30px;
      border-radius: 12px;
      margin: 20px 0;
    }
    
    .confirmation-item {
      padding: 20px;
      background: white;
      border-radius: 8px;
      margin-bottom: 15px;
      border-left: 5px solid #FF9800;
    }
    
    .confirmation-label {
      color: #666;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .confirmation-value {
      color: #333;
      font-size: 1.2rem;
      font-weight: 700;
    }
    
    .form-actions {
      display: flex;
      gap: 15px;
      justify-content: flex-end;
      margin-top: 30px;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: white;
      padding: 15px 30px;
      border-radius: 8px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, #F57C00, #E65100);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
    }
    
    .btn-primary:disabled {
      background: #E0E0E0;
      color: #9E9E9E;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .btn-secondary {
      background: white;
      color: #666;
      padding: 15px 30px;
      border-radius: 8px;
      border: 2px solid #E0E0E0;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }
    
    .btn-secondary:hover {
      border-color: #FF9800;
      color: #FF9800;
    }
    
    .btn-large {
      padding: 18px 40px;
      font-size: 1.1rem;
    }
    
    .alert-success {
      background: #E8F5E9;
      border-left: 4px solid #4CAF50;
      color: #2E7D32;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .alert-error {
      background: #FFEBEE;
      border-left: 4px solid #F44336;
      color: #C62828;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
      .step-indicator {
        flex-direction: column;
        gap: 20px;
      }
      
      .step-line {
        display: none;
      }
      
      .time-slots {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
      }
      
      .form-actions {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <!-- ヘッダー -->
  <header class="reserve-header">
    <div class="header-content">
      <div class="logo-section">
        <h1>キャリアトレーナーズ</h1>
        <p>Career Trainers - 練習予約</p>
      </div>
      <a href="../../mypage.php" class="btn-back">
        <i data-lucide="arrow-left"></i>
        マイページに戻る
      </a>
    </div>
  </header>

  <div class="container-reserve">
    <!-- 試験情報セクション -->
    <div class="info-section">
      <h2 class="info-title">
        <i data-lucide="graduation-cap"></i>
        第31回実技試験について
      </h2>
      
      <div class="exam-info-box">
        <h3>
          <i data-lucide="calendar-check"></i>
          試験日程
        </h3>
        <div class="exam-dates">
          2026年3月7日(土), 8日(日), 13日(金), 14日(土), 15日(日), 20日(金), 21日(土), 22日(日)
        </div>
        <p style="color: #666; margin: 10px 0 0 0; line-height: 1.7;">
          上記の試験日に向けて、十分な練習時間を確保しましょう。
        </p>
      </div>
      
      <div class="time-breakdown">
        <h4>
          <i data-lucide="clock"></i>
          1回の練習セッションについて
        </h4>
        <ul>
          <li><strong>ペルソナお渡し:</strong> 約5分（役柄の説明・準備）</li>
          <li><strong>面接試験:</strong> 20分
            <ul style="margin-top: 5px;">
              <li>ロールプレイ: 15分</li>
              <li>口頭試問: 5分</li>
            </ul>
          </li>
          <li><strong>相互フィードバック:</strong> 約15分（振り返り・改善点の共有）</li>
        </ul>
        <div class="total-time">
          合計: 約45分
        </div>
      </div>
    </div>

    <!-- 予約可能時間帯セクション -->
    <div class="info-section">
      <h2 class="info-title">
        <i data-lucide="clock"></i>
        予約可能時間帯
      </h2>
      
      <div class="availability-note">
        <strong>予約受付期間:</strong> 2026年2月1日〜2026年3月21日<br><br>
        <strong>平日（月〜金）:</strong> 17:00〜21:00<br>
        <strong>土日・祝日:</strong> 08:00〜21:00<br><br>
        ※ 1時間ごとの枠で予約が可能です。<br>
        ※ キャリアコンサルタントの承認後、Google Meetのリンクが自動生成されます。
      </div>
    </div>

    <!-- 予約フォームカード -->
    <div class="form-card">
      <?php if (!$can_reserve): ?>
        <div class="alert-error">
          <i data-lucide="alert-circle"></i>
          予約上限に達しています。予約は最大<?= $max_reservations ?>回までです（現在: <?= $total_reservations ?>回）。
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert-success">
          <?= h($success) ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert-error">
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="alert-error">
          <ul style="margin: 0; padding-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
              <li><?= h($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($can_reserve): ?>
      <form id="reservationForm" action="../../../../controller/user/reserve_create.php" method="POST">
        
        <!-- ステップインジケーター -->
        <div class="step-indicator">
          <div class="step active" data-step="1">
            <div class="step-circle">1</div>
            <div class="step-label">日付</div>
          </div>
          <div class="step-line"></div>
          <div class="step" data-step="2">
            <div class="step-circle">2</div>
            <div class="step-label">時間</div>
          </div>
          <div class="step-line"></div>
          <div class="step" data-step="3">
            <div class="step-circle">3</div>
            <div class="step-label">確認</div>
          </div>
        </div>

        <!-- ステップ1: 日付選択 -->
        <div class="form-step active" id="step1">
          <h3 class="step-title">
            <i data-lucide="calendar"></i>
            日付を選択してください
          </h3>
          
          <div class="calendar-container">
            <div class="calendar-header">
              <button type="button" class="calendar-nav" id="prevMonth">
                <i data-lucide="chevron-left"></i>
              </button>
              <div class="calendar-title" id="calendarTitle">2026年2月</div>
              <button type="button" class="calendar-nav" id="nextMonth">
                <i data-lucide="chevron-right"></i>
              </button>
            </div>
            
            <div class="calendar-weekdays">
              <div class="weekday">日</div>
              <div class="weekday">月</div>
              <div class="weekday">火</div>
              <div class="weekday">水</div>
              <div class="weekday">木</div>
              <div class="weekday">金</div>
              <div class="weekday">土</div>
            </div>
            
            <div class="calendar-days" id="calendarDays">
              <!-- カレンダーの日付はJavaScriptで生成 -->
            </div>
          </div>

          <input type="hidden" name="selected_date" id="selectedDate">
          
          <div class="form-actions">
            <button type="button" class="btn-primary btn-large" id="nextToTime" disabled>
              次へ：時間を選択
              <i data-lucide="arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- ステップ2: 時間選択 -->
        <div class="form-step" id="step2">
          <h3 class="step-title">
            <i data-lucide="clock"></i>
            時間を選択してください
          </h3>
          
          <div class="selected-info">
            <i data-lucide="calendar"></i>
            <span id="displaySelectedDate">選択された日付</span>
          </div>
          
          <div class="time-slots" id="timeSlots">
            <!-- 時間スロットはJavaScriptで生成 -->
          </div>

          <input type="hidden" name="selected_time" id="selectedTime">
          
          <div class="form-actions">
            <button type="button" class="btn-secondary" id="backToDate">
              <i data-lucide="arrow-left"></i>
              戻る
            </button>
            <button type="button" class="btn-primary btn-large" id="nextToConfirm" disabled>
              次へ：確認
              <i data-lucide="arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- ステップ3: 最終確認 -->
        <div class="form-step" id="step3">
          <h3 class="step-title">
            <i data-lucide="check-circle"></i>
            予約内容の確認
          </h3>
          
          <div class="confirmation-section">
            <div class="confirmation-item">
              <div class="confirmation-label">
                <i data-lucide="calendar"></i>
                日付
              </div>
              <div class="confirmation-value" id="confirmDate">-</div>
            </div>
            
            <div class="confirmation-item">
              <div class="confirmation-label">
                <i data-lucide="clock"></i>
                時間
              </div>
              <div class="confirmation-value" id="confirmTime">-</div>
            </div>
            
            <div style="background: #FFF3E0; padding: 20px; border-radius: 8px; border-left: 4px solid #FF9800; margin-top: 20px;">
              <p style="margin: 0; color: #E65100; font-weight: 600; margin-bottom: 10px;">
                <i data-lucide="info" style="width: 18px; height: 18px; display: inline; vertical-align: text-bottom;"></i>
                予約確定後の流れ
              </p>
              <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #666; line-height: 1.8;">
                <li>キャリアコンサルタントが予約を承認します</li>
                <li>承認後、Google Meetのリンクが自動生成されます</li>
                <li>マイページから練習セッションの詳細を確認できます</li>
                <li>当日はGoogle Meetリンクから参加してください</li>
              </ul>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" id="backToTime">
              <i data-lucide="arrow-left"></i>
              戻る
            </button>
            <button type="submit" class="btn-primary btn-large">
              予約を確定する
              <i data-lucide="check"></i>
            </button>
          </div>
        </div>

      </form>
      <?php else: ?>
        <div style="text-align: center; padding: 40px 20px;">
          <p style="color: #E65100; font-size: 1.1rem; margin-bottom: 20px;">
            予約上限（<?= $max_reservations ?>回）に達したため、新規予約はできません。
          </p>
          <a href="../../mypage.php" class="btn-secondary">
            <i data-lucide="arrow-left"></i>
            マイページに戻る
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <?php if ($can_reserve): ?>
  <!-- 予約カレンダーのスクリプト -->
  <script>
    // 初期化（2026年2月から開始）
    let currentDate = new Date('2026-02-01');
    let selectedDate = null;
    let selectedTime = null;

    // 予約可能期間の設定（第31回実技試験対応）
    const RESERVATION_START = new Date('2026-02-01');
    const RESERVATION_END = new Date('2026-03-21');
    RESERVATION_START.setHours(0, 0, 0, 0);
    RESERVATION_END.setHours(23, 59, 59, 999);

    // 祝日リスト（2026年）
    const holidays = [
      '2026-02-11', // 建国記念の日
      '2026-02-23', // 天皇誕生日
      '2026-03-20', // 春分の日
    ];

    // 日付が土日祝日かチェック
    function isWeekendOrHoliday(dateString) {
      const date = new Date(dateString);
      const dayOfWeek = date.getDay();
      const formattedDate = dateString; // YYYY-MM-DD形式
      return dayOfWeek === 0 || dayOfWeek === 6 || holidays.includes(formattedDate);
    }

    // カレンダー生成
    function generateCalendar(year, month) {
      const calendarDays = document.getElementById('calendarDays');
      const calendarTitle = document.getElementById('calendarTitle');
      
      calendarDays.innerHTML = '';
      calendarTitle.textContent = `${year}年${month + 1}月`;
      
      const firstDay = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      // 前月の空白
      for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day empty';
        calendarDays.appendChild(emptyDay);
      }
      
      // 日付
      for (let day = 1; day <= daysInMonth; day++) {
        const dateObj = new Date(year, month, day);
        dateObj.setHours(0, 0, 0, 0);
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        // タイムゾーン対応：YYYY-MM-DD形式を直接生成
        const yyyy = year;
        const mm = String(month + 1).padStart(2, '0');
        const dd = String(day).padStart(2, '0');
        const dateString = `${yyyy}-${mm}-${dd}`;
        dayElement.dataset.date = dateString;
        
        // 予約可能期間外、または過去の日付は選択不可
        if (dateObj < today || dateObj < RESERVATION_START || dateObj > RESERVATION_END) {
          dayElement.classList.add('disabled');
        } else {
          dayElement.addEventListener('click', function() {
            selectDate(this);
          });
        }
        
        calendarDays.appendChild(dayElement);
      }
      
      lucide.createIcons();
    }

    // 日付選択
    function selectDate(element) {
      if (element.classList.contains('disabled')) return;
      
      document.querySelectorAll('.calendar-day').forEach(day => {
        day.classList.remove('selected');
      });
      
      element.classList.add('selected');
      selectedDate = element.dataset.date;
      document.getElementById('selectedDate').value = selectedDate;
      document.getElementById('nextToTime').disabled = false;
    }

    // 時間スロット生成（平日17:00-21:00、土日祝日08:00-21:00）
    function generateTimeSlots() {
      const timeSlots = document.getElementById('timeSlots');
      timeSlots.innerHTML = '';
      
      // 選択された日付が土日祝日かチェック
      const isHoliday = isWeekendOrHoliday(selectedDate);
      
      // 時間帯を決定
      const startHour = isHoliday ? 8 : 17;  // 土日祝日は8時から、平日は17時から
      const endHour = 21;  // 21時まで
      
      // 説明文を追加
      const infoDiv = document.createElement('div');
      infoDiv.style.cssText = 'background: #E3F2FD; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #1565C0; font-size: 0.95rem;';
      infoDiv.innerHTML = isHoliday 
        ? '<strong>土日祝日:</strong> 08:00〜21:00の時間帯で予約可能です'
        : '<strong>平日:</strong> 17:00〜21:00の時間帯で予約可能です';
      timeSlots.appendChild(infoDiv);
      
      for (let hour = startHour; hour <= endHour; hour++) {
        const timeSlot = document.createElement('button');
        timeSlot.type = 'button';
        timeSlot.className = 'time-slot';
        timeSlot.textContent = `${String(hour).padStart(2, '0')}:00`;
        timeSlot.dataset.time = `${String(hour).padStart(2, '0')}:00`;
        
        timeSlot.addEventListener('click', function() {
          selectTime(this);
        });
        
        timeSlots.appendChild(timeSlot);
      }
    }

    // 時間選択
    function selectTime(element) {
      document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
      });
      
      element.classList.add('selected');
      selectedTime = element.dataset.time;
      document.getElementById('selectedTime').value = selectedTime;
      document.getElementById('nextToConfirm').disabled = false;
    }

    // ステップ移動
    function goToStep(stepNumber) {
      document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
      });
      document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active', 'completed');
      });
      
      document.getElementById(`step${stepNumber}`).classList.add('active');
      
      for (let i = 1; i <= stepNumber; i++) {
        const stepIndicator = document.querySelector(`.step[data-step="${i}"]`);
        if (i === stepNumber) {
          stepIndicator.classList.add('active');
        } else if (i < stepNumber) {
          stepIndicator.classList.add('completed');
        }
      }
      
      lucide.createIcons();
    }

    // 日付フォーマット
    function formatDate(dateString) {
      const date = new Date(dateString);
      const year = date.getFullYear();
      const month = date.getMonth() + 1;
      const day = date.getDate();
      const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
      const weekday = weekdays[date.getDay()];
      return `${year}年${month}月${day}日（${weekday}）`;
    }

    // イベントリスナー
    document.getElementById('prevMonth').addEventListener('click', function() {
      currentDate.setMonth(currentDate.getMonth() - 1);
      generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    });

    document.getElementById('nextMonth').addEventListener('click', function() {
      currentDate.setMonth(currentDate.getMonth() + 1);
      generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    });

    document.getElementById('nextToTime').addEventListener('click', function() {
      if (selectedDate) {
        document.getElementById('displaySelectedDate').textContent = formatDate(selectedDate);
        generateTimeSlots();
        goToStep(2);
      }
    });

    document.getElementById('backToDate').addEventListener('click', function() {
      goToStep(1);
    });

    document.getElementById('nextToConfirm').addEventListener('click', function() {
      if (selectedDate && selectedTime) {
        document.getElementById('confirmDate').textContent = formatDate(selectedDate);
        document.getElementById('confirmTime').textContent = selectedTime + ' - ' + (parseInt(selectedTime) + 1) + ':00';
        goToStep(3);
      }
    });

    document.getElementById('backToTime').addEventListener('click', function() {
      goToStep(2);
    });

    // 初期化
    generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    lucide.createIcons();
  </script>
  <?php endif; ?>
  
  <script>
    // Lucideアイコンの初期化（常に実行）
    lucide.createIcons();
  </script>
</body>
</html>
