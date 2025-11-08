<?php
/**
 * クライアント（キャリア相談者） ログイン処理
 * 
 * @package CareerTre
 */

// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

// 共通処理読み込み
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/helpers.php';

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/gs_code/gga/page/client/login.php');
}

// POSTデータ取得
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// バリデーション
$errors = [];

// メールアドレスチェック
if (!validateRequired($email)) {
    $errors[] = 'メールアドレスを入力してください';
} elseif (!validateEmail($email)) {
    $errors[] = '有効なメールアドレスを入力してください';
}

// パスワードチェック
if (!validateRequired($password)) {
    $errors[] = 'パスワードを入力してください';
}

// エラーがある場合はログインページに戻る
if (!empty($errors)) {
    setSessionMessage('error', implode('<br>', $errors));
    setSessionMessage('old_email', $email);
    redirect('/gs_code/gga/page/client/login.php');
}

// データベース接続
try {
    $db = getDBConnection();
    
    // ユーザー情報を取得
    $stmt = $db->prepare("SELECT id, name, email, password, is_active FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch();
    
    // ユーザーが存在しない、またはパスワードが間違っている
    if (!$client || !password_verify($password, $client['password'])) {
        setSessionMessage('error', 'メールアドレスまたはパスワードが間違っています');
        setSessionMessage('old_email', $email);
        redirect('/gs_code/gga/page/client/login.php');
    }
    
    // アカウントが無効
    if (!$client['is_active']) {
        setSessionMessage('error', 'このアカウントは無効化されています');
        redirect('/gs_code/gga/page/client/login.php');
    }
    
    // セッションにユーザー情報を保存
    $_SESSION['user_id'] = $client['id'];
    $_SESSION['user_type'] = 'client';
    $_SESSION['user_name'] = $client['name'];
    $_SESSION['user_email'] = $client['email'];
    
    // ログイン成功 - クライアントマイページにリダイレクト
    setSessionMessage('success', 'ログインしました');
    redirect('/gs_code/gga/page/client/mypage.php');
    
} catch (PDOException $e) {
    error_log('Client Login Error: ' . $e->getMessage());
    setSessionMessage('error', 'ログイン処理中にエラーが発生しました');
    setSessionMessage('old_email', $email);
    redirect('/gs_code/gga/page/client/login.php');
}
