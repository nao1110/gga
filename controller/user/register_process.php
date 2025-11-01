<?php
/**
 * 受験者新規登録処理
 * 
 * @package CareerTre
 */

// 共通処理読み込み
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/helpers.php';

// セッション開始
session_start();

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/gs_code/gga/page/user/register.php');
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
} elseif (!validatePassword($password, 8)) {
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
    redirect('/gs_code/gga/page/user/register.php');
}

// データベース接続
try {
    $db = getDBConnection();
    
    // メールアドレス重複チェック
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        setSessionMessage('error', 'このメールアドレスは既に登録されています');
        setSessionMessage('old_name', $name);
        setSessionMessage('old_email', $email);
        redirect('/gs_code/gga/page/user/register.php');
    }
    
    // パスワードハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // ユーザー登録
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, ticket_count, is_active, created_at, updated_at)
        VALUES (?, ?, ?, 5, 1, NOW(), NOW())
    ");
    $stmt->execute([$name, $email, $hashed_password]);
    
    // 登録成功
    setSessionMessage('success', '新規登録が完了しました。ログインしてください。');
    redirect('/gs_code/gga/page/user/login.php');
    
} catch (PDOException $e) {
    error_log('Register Error (User): ' . $e->getMessage());
    setSessionMessage('error', '登録処理中にエラーが発生しました');
    setSessionMessage('old_name', $name);
    setSessionMessage('old_email', $email);
    redirect('/gs_code/gga/page/user/register.php');
}
