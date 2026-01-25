<?php
/**
 * トレーナーログイン処理
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
    redirect('/gs_code/gga/page/trainer/login.php');
}

// POSTデータ取得
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// バリデーション
$errors = [];

if (!validateRequired($email)) {
    $errors[] = 'メールアドレスを入力してください';
} elseif (!validateEmail($email)) {
    $errors[] = '有効なメールアドレスを入力してください';
}

if (!validateRequired($password)) {
    $errors[] = 'パスワードを入力してください';
}

// エラーがある場合はログインページに戻る
if (!empty($errors)) {
    setSessionMessage('errors', $errors);
    setSessionMessage('old_email', $email);
    redirect('/gs_code/gga/page/trainer/login.php');
}

// データベース接続
try {
    $db = getDBConnection();
    
    // トレーナー検索（メールアドレス）
    $stmt = $db->prepare("
        SELECT id, name, email, password, is_active 
        FROM trainers 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $trainer = $stmt->fetch();
    
    // トレーナーが存在しない、またはパスワードが一致しない
    if (!$trainer || !password_verify($password, $trainer['password'])) {
        setSessionMessage('error', 'メールアドレスまたはパスワードが正しくありません');
        setSessionMessage('old_email', $email);
        redirect('/gs_code/gga/page/trainer/login.php');
    }
    
    // アカウントが無効化されている
    if (!$trainer['is_active']) {
        setSessionMessage('error', 'このアカウントは無効化されています。管理者にお問い合わせください。');
        redirect('/gs_code/gga/page/trainer/login.php');
    }
    
    // ログイン成功
    loginUser($trainer['id'], 'trainer', $trainer['name'], $trainer['email']);
    
    // トップページにリダイレクト
    setSessionMessage('success', 'ログインしました');
    redirect('/gs_code/gga/page/index.php');
    
} catch (PDOException $e) {
    error_log('Login Error (Trainer): ' . $e->getMessage());
    setSessionMessage('error', 'ログイン処理中にエラーが発生しました');
    redirect('/gs_code/gga/page/trainer/login.php');
}
