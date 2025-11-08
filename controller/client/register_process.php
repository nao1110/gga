<?php
/**
 * クライアント（キャリア相談者） 登録処理
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
    redirect('/gs_code/gga/page/client/register.php');
}

// POSTデータ取得
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// バリデーション
$errors = [];

// 名前チェック
if (!validateRequired($name)) {
    $errors[] = '名前を入力してください';
} elseif (!validateLength($name, 1, 100)) {
    $errors[] = '名前は100文字以内で入力してください';
}

// メールアドレスチェック
if (!validateRequired($email)) {
    $errors[] = 'メールアドレスを入力してください';
} elseif (!validateEmail($email)) {
    $errors[] = '有効なメールアドレスを入力してください';
}

// パスワードチェック
if (!validateRequired($password)) {
    $errors[] = 'パスワードを入力してください';
} elseif (!validateLength($password, 8)) {
    $errors[] = 'パスワードは8文字以上で入力してください';
}

// パスワード確認チェック
if ($password !== $password_confirm) {
    $errors[] = 'パスワードが一致しません';
}

// エラーがある場合は登録ページに戻る
if (!empty($errors)) {
    setSessionMessage('errors', $errors);
    setSessionMessage('old_name', $name);
    setSessionMessage('old_email', $email);
    redirect('/gs_code/gga/page/client/register.php');
}

// データベース接続
try {
    $db = getDBConnection();
    
    // メールアドレスの重複チェック
    $stmt = $db->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        setSessionMessage('error', 'このメールアドレスは既に登録されています');
        setSessionMessage('old_name', $name);
        setSessionMessage('old_email', $email);
        redirect('/gs_code/gga/page/client/register.php');
    }
    
    // パスワードのハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // ユーザーを登録
    $stmt = $db->prepare("
        INSERT INTO clients (name, email, password, created_at, updated_at) 
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$name, $email, $hashed_password]);
    
    // 登録成功
    setSessionMessage('success', '登録が完了しました。ログインしてください。');
    redirect('/gs_code/gga/page/client/login.php');
    
} catch (PDOException $e) {
    error_log('Client Register Error: ' . $e->getMessage());
    setSessionMessage('error', '登録処理中にエラーが発生しました');
    setSessionMessage('old_name', $name);
    setSessionMessage('old_email', $email);
    redirect('/gs_code/gga/page/client/register.php');
}
