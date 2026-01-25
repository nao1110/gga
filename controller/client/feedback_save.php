<?php
/**
 * クライアント 自己フィードバック保存処理
 * 
 * @package CareerTre
 */

// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 共通処理読み込み
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/helpers.php';

// 認証チェック
requireLogin('client');

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$client_id = $current_user['id'];

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/gs_code/gga/page/client/mypage.php');
}

// POSTデータ取得
$reserve_id = intval($_POST['reserve_id'] ?? 0);
$client_feedback = trim($_POST['client_feedback'] ?? '');

// バリデーション
$errors = [];

if ($reserve_id <= 0) {
    $errors[] = '予約IDが不正です';
}

if (!validateRequired($client_feedback)) {
    $errors[] = '自己フィードバックを入力してください';
}

// エラーがある場合
if (!empty($errors)) {
    setSessionMessage('error', implode('<br>', $errors));
    redirect('/gs_code/gga/page/client/mypage.php');
}

// データベース接続
try {
    $db = getDBConnection();
    
    // 予約が自分のものか確認
    $stmt = $db->prepare("SELECT id FROM client_reserves WHERE id = ? AND client_id = ? AND status = 'completed'");
    $stmt->execute([$reserve_id, $client_id]);
    $reserve = $stmt->fetch();
    
    if (!$reserve) {
        setSessionMessage('error', '予約が見つからないか、フィードバックを入力できる状態ではありません');
        redirect('/gs_code/gga/page/client/mypage.php');
    }
    
    // 自己フィードバックを保存
    $stmt = $db->prepare("
        UPDATE client_reserves 
        SET 
            client_feedback = ?,
            client_feedback_date = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$client_feedback, $reserve_id]);
    
    setSessionMessage('success', '自己フィードバックを保存しました');
    redirect('/gs_code/gga/page/client/mypage.php');
    
} catch (PDOException $e) {
    error_log('Client Feedback Save Error: ' . $e->getMessage());
    setSessionMessage('error', 'フィードバックの保存中にエラーが発生しました');
    redirect('/gs_code/gga/page/client/mypage.php');
}
