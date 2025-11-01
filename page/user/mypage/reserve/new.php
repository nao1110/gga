<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>新規予約 - CareerTre キャリトレ</title>
  
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
  <main class="hero-container">
    <div class="container-narrow">
      
      <!-- ヘッダー -->
      <header class="page-header fade-in">
        <a href="../../mypage.php" class="back-link">
          <i data-lucide="arrow-left"></i>
          マイページに戻る
        </a>
        <h2 class="page-title">実技試験練習の予約</h2>
        <p class="page-description">希望の日時を選択してください</p>
      </header>

      <!-- 予約フォーム -->
      <section class="form-section fade-in">
        <article class="form-card reservation-card">
          
          <form id="reservationForm" action="reserve_process.php" method="POST">
            
            <!-- ステップインジケーター -->
            <div class="step-indicator">
              <div class="step active" data-step="1">
                <div class="step-circle">1</div>
                <div class="step-label">日付選択</div>
              </div>
              <div class="step-line"></div>
              <div class="step" data-step="2">
                <div class="step-circle">2</div>
                <div class="step-label">時間選択</div>
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
                  <div class="calendar-title" id="calendarTitle">2025年11月</div>
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

            <!-- ステップ3: 確認 -->
            <div class="form-step" id="step3">
              <h3 class="step-title">
                <i data-lucide="check-circle"></i>
                予約内容の確認
              </h3>
              
              <div class="confirmation-card">
                <div class="confirmation-item">
                  <div class="confirmation-label">
                    <i data-lucide="calendar"></i>
                    予約日
                  </div>
                  <div class="confirmation-value" id="confirmDate">-</div>
                </div>
                
                <div class="confirmation-item">
                  <div class="confirmation-label">
                    <i data-lucide="clock"></i>
                    予約時間
                  </div>
                  <div class="confirmation-value" id="confirmTime">-</div>
                </div>
                
                <div class="confirmation-item">
                  <div class="confirmation-label">
                    <i data-lucide="timer"></i>
                    練習時間
                  </div>
                  <div class="confirmation-value">60分</div>
                </div>
              </div>

              <div class="confirmation-note">
                <i data-lucide="info"></i>
                <p>予約リクエストを送信します。キャリアコンサルタントが確定すると、メールでお知らせします。</p>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn-secondary" id="backToTime">
                  <i data-lucide="arrow-left"></i>
                  戻る
                </button>
                <button type="submit" class="btn-primary btn-large">
                  <i data-lucide="send"></i>
                  予約リクエストを送信
                </button>
              </div>
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
  
  <!-- 予約カレンダーのスクリプト -->
  <script>
    // 初期化
    let currentDate = new Date();
    let selectedDate = null;
    let selectedTime = null;

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
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        dayElement.dataset.date = dateObj.toISOString().split('T')[0];
        
        // 過去の日付は選択不可
        if (dateObj < today) {
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

    // 時間スロット生成
    function generateTimeSlots() {
      const timeSlots = document.getElementById('timeSlots');
      timeSlots.innerHTML = '';
      
      for (let hour = 8; hour <= 21; hour++) {
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

    document.getElementById('reservationForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      // 実際の実装ではここでサーバーに送信
      alert('予約リクエストを送信しました！\nマイページに戻ります。');
      window.location.href = '../../mypage.php';
    });

    // 初期化
    generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    lucide.createIcons();
  </script>
</body>
</html>
