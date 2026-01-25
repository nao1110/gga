<?php
// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

// 共通処理読み込み
require_once __DIR__ . '/../lib/helpers.php';

// メッセージ取得
$success = getSessionMessage('success');
$error = getSessionMessage('error');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CareerTre - キャリアトレーナーズ | キャリア相談を日常の身近なものに</title>
  
  <!-- Pico.css CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  
  <!-- カスタムCSS -->
  <link rel="stylesheet" href="../assets/css/variables.css?v=2.1">
  <link rel="stylesheet" href="../assets/css/custom.css?v=2.1">
</head>
<body style="background: white; margin: 0; padding: 0;">
  <!-- メインコンテナ -->
  <main style="max-width: 100%; margin: 0; padding: 0;">
    
    <!-- ヒーローセクション -->
    <section style="background: white; padding: 80px 20px 60px; text-align: center; position: relative;">
      <div style="max-width: 1200px; margin: 0 auto;">
        
        <!-- メインタイトル -->
        <h1 style="font-size: 3.5rem; font-weight: 900; color: #333; margin: 0 0 10px 0; letter-spacing: 2px;">
          キャリアトレーナーズ
        </h1>
        
        <!-- サブタイトル -->
        <p style="font-size: 1.2rem; color: #FF9800; font-weight: 600; letter-spacing: 3px; margin: 0 0 40px 0;">
          Career Trainers
        </p>
        
        <!-- メインメッセージ -->
        <div style="background: linear-gradient(135deg, #FFE0B2 0%, #FFF8E1 100%); padding: 40px 30px 50px; border-radius: 20px; margin: 40px auto; max-width: 800px; position: relative; box-shadow: 0 4px 20px rgba(255, 152, 0, 0.15);">
          <h2 style="font-size: 2.5rem; font-weight: 900; color: #FF9800; margin: 0 0 20px 0; line-height: 1.4;">
            ロールプレイで実力UP
          </h2>
          <p style="font-size: 1.1rem; color: #333; line-height: 1.8; margin: 0; text-align: left; padding: 0 20px;">
            ⇨キャリアコンサルタント資格取得者による、試験受験者のロープレ実技試験対策練習道場です。<br>
            合格を目指して頑張りましょう！
          </p>
          <!-- 波線装飾 -->
          <svg style="position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%); width: 80%; height: 30px;" viewBox="0 0 800 30" xmlns="http://www.w3.org/2000/svg">
            <path d="M 0 15 Q 100 0, 200 15 T 400 15 T 600 15 T 800 15" stroke="#FF9800" stroke-width="3" fill="none"/>
          </svg>
        </div>
      </div>
    </section>
    
    <!-- カードセクション -->
    <section style="background: #FAFAFA; padding: 80px 20px;">
      <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
          
          <!-- キャリアコンサルタント有資格者 -->
          <div style="background: white; border-radius: 15px; padding: 40px 30px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #FFE0B2, #FFF8E1); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#FF9800" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <polyline points="16 11 18 13 22 9"></polyline>
              </svg>
            </div>
            <h3 style="font-size: 1.3rem; font-weight: 700; color: #333; margin: 0 0 20px 0;">キャリアコンサルタント<br>有資格者</h3>
            <a href="trainer/login.php" style="display: block; background: #FF9800; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 10px;">ログイン</a>
            <a href="trainer/register.php" style="display: block; background: white; color: #FF9800; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid #FF9800; margin-bottom: 10px;">新規登録</a>
            <a href="../controller/trainer/google_login.php" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: white; color: #3c4043; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid #dadce0; font-size: 0.9rem;">
              <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                <path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957C.347 6.175 0 7.55 0 9s.348 2.825.957 4.039l3.007-2.332z"/>
                <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.163 6.656 3.58 9 3.58z"/>
              </svg>
              Googleでログイン
            </a>
          </div>
          
          <!-- キャリアコンサルタント受験者 -->
          <div style="background: white; border-radius: 15px; padding: 40px 30px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #FFE0B2, #FFF8E1); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#FF9800" stroke-width="2">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
              </svg>
            </div>
            <h3 style="font-size: 1.3rem; font-weight: 700; color: #333; margin: 0 0 20px 0;">キャリアコンサルタント<br>受験者</h3>
            <a href="user/login.php" style="display: block; background: #FF9800; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 10px;">ログイン</a>
            <a href="user/register.php" style="display: block; background: white; color: #FF9800; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid #FF9800; margin-bottom: 10px;">新規登録</a>
            <a href="../controller/user/google_login.php" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: white; color: #3c4043; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid #dadce0; font-size: 0.9rem;">
              <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                <path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957C.347 6.175 0 7.55 0 9s.348 2.825.957 4.039l3.007-2.332z"/>
                <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.163 6.656 3.58 9 3.58z"/>
              </svg>
              Googleでログイン
            </a>
          </div>
          
          <!-- キャリア相談者 -->
          <div style="background: white; border-radius: 15px; padding: 40px 30px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); opacity: 0.6;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #E0E0E0, #F5F5F5); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <h3 style="font-size: 1.3rem; font-weight: 700; color: #333; margin: 0 0 20px 0;">キャリア<br>相談者</h3>
            <div style="background: #F5F5F5; padding: 15px; border-radius: 8px;">
              <p style="margin: 0; color: #666; font-weight: 600;">準備中</p>
            </div>
          </div>
          
        </div>
      </div>
    </section>
    
    <!-- フッター -->
    <footer style="background: #333; color: white; text-align: center; padding: 30px 20px;">
      <p style="margin: 0;">&copy; 2025 CareerTre - キャリアトレーナーズ All rights reserved.</p>
    </footer>

  </main>
</body>
</html>
