<?php
/**
 * トレーナー Google OAuth コールバック処理
 * 
 * @package CareerTre
 */

// エラー表示を有効化（デバッグ用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// セッション開始
session_start();

// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

// Composer autoload
require_once __DIR__ . '/../../vendor/autoload.php';

// 共通処理読み込み
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/google_auth.php';

// Google Clientの初期化
$client = getGoogleClient();

// 認証コードを取得
$code = $_GET['code'] ?? '';

if (!$code) {
    setSessionMessage('error', '認証コードが取得できませんでした');
    redirect('/gs_code/gga/page/trainer/login.php');
}

try {
    // 認証コードからアクセストークンを取得
    $token = $client->fetchAccessTokenWithAuthCode($code);
    
    if (isset($token['error'])) {
        throw new Exception($token['error']);
    }
    
    // アクセストークンを設定
    $client->setAccessToken($token);
    
    // ユーザー情報を取得（Google API Client v2の方法）
    $payload = $client->verifyIdToken();
    
    if (!$payload) {
        throw new Exception('ID トークンの検証に失敗しました');
    }
    
    $email = $payload['email'];
    $name = $payload['name'];
    $google_id = $payload['sub']; // 'sub' is the Google user ID
    
    // データベース接続
    $db = getDBConnection();
    
    // トレーナーが存在するか確認
    $stmt = $db->prepare("SELECT id, name, email, is_active FROM trainers WHERE email = ?");
    $stmt->execute([$email]);
    $trainer = $stmt->fetch();
    
    if ($trainer) {
        // 既存のトレーナー
        if (!$trainer['is_active']) {
            setSessionMessage('error', 'このアカウントは無効化されています');
            redirect('/gs_code/gga/page/trainer/login.php');
        }
        
        // Google IDを更新
        $stmt = $db->prepare("UPDATE trainers SET google_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$google_id, $trainer['id']]);
        
    } else {
        // 新規トレーナーとして登録
        $stmt = $db->prepare("
            INSERT INTO trainers (name, email, google_id, password, created_at, updated_at) 
            VALUES (?, ?, ?, '', NOW(), NOW())
        ");
        $stmt->execute([$name, $email, $google_id]);
        $trainer_id = $db->lastInsertId();
        
        // 新規登録したトレーナー情報を取得
        $stmt = $db->prepare("SELECT id, name, email, is_active FROM trainers WHERE id = ?");
        $stmt->execute([$trainer_id]);
        $trainer = $stmt->fetch();
    }
    
    // セッションにユーザー情報を保存
    $_SESSION['user_id'] = $trainer['id'];
    $_SESSION['user_type'] = 'trainer';
    $_SESSION['user_name'] = $trainer['name'];
    $_SESSION['user_email'] = $trainer['email'];
    
    // ログイン成功
    setSessionMessage('success', 'Googleアカウントでログインしました');
    redirect('/gs_code/gga/page/trainer/mypage.php');
    
} catch (Exception $e) {
    error_log('Google OAuth Error: ' . $e->getMessage());
    setSessionMessage('error', 'Google認証に失敗しました: ' . $e->getMessage());
    redirect('/gs_code/gga/page/trainer/login.php');
}
