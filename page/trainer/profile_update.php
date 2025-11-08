<?php
/**
 * トレーナー プロフィール更新処理
 * 
 * @package CareerTre
 */

// 共通処理読み込み
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/helpers.php';

// 認証チェック
requireLogin('trainer');

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/gs_code/gga/page/trainer/profile.php');
}

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$trainer_id = $current_user['id'];

// POSTデータ取得
$nickname = trim($_POST['nickname'] ?? '');
$career_description = trim($_POST['career_description'] ?? '');
$available_time = trim($_POST['available_time'] ?? '');

// バリデーション
$errors = [];

// ニックネームチェック
if (!validateRequired($nickname)) {
    $errors[] = 'ニックネームを入力してください';
} elseif (mb_strlen($nickname) > 100) {
    $errors[] = 'ニックネームは100文字以内で入力してください';
}

// 経歴チェック
if (!validateRequired($career_description)) {
    $errors[] = '経歴を入力してください';
} elseif (mb_strlen($career_description) < 20) {
    $errors[] = '経歴は20文字以上で入力してください';
}

// 対応可能時間チェック
if (!validateRequired($available_time)) {
    $errors[] = '対応可能時間を入力してください';
} elseif (mb_strlen($available_time) < 10) {
    $errors[] = '対応可能時間は10文字以上で入力してください';
}

// エラーがある場合はプロフィールページに戻る
if (!empty($errors)) {
    setSessionMessage('errors', $errors);
    redirect('/gs_code/gga/page/trainer/profile.php');
}

// データベース接続
try {
    $db = getDBConnection();
    
    // プロフィール情報を更新
    $stmt = $db->prepare("
        UPDATE trainers 
        SET nickname = ?,
            career_description = ?,
            available_time = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $nickname,
        $career_description,
        $available_time,
        $trainer_id
    ]);
    
    // セッションのユーザー情報も更新
    $_SESSION['user']['nickname'] = $nickname;
    
    // 成功メッセージを設定してプロフィールページにリダイレクト
    setSessionMessage('success', 'プロフィールを更新しました');
    redirect('/gs_code/gga/page/trainer/profile.php');
    
} catch (PDOException $e) {
    error_log('Profile Update Error: ' . $e->getMessage());
    setSessionMessage('error', 'プロフィール更新中にエラーが発生しました');
    redirect('/gs_code/gga/page/trainer/profile.php');
}
