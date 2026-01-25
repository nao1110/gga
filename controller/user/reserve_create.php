<?php
/**
 * 受験者 予約作成処理
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

// 認証チェック
requireLogin('user');

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/gs_code/gga/page/user/mypage/reserve/new.php');
}

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$user_id = $current_user['id'];

// POSTデータ取得
$selected_date = $_POST['selected_date'] ?? '';
$selected_time = $_POST['selected_time'] ?? '';

// バリデーション
$errors = [];

// 日付チェック
if (!validateRequired($selected_date)) {
    $errors[] = '日付を選択してください';
} else {
    $date = DateTime::createFromFormat('Y-m-d', $selected_date);
    if (!$date || $date->format('Y-m-d') !== $selected_date) {
        $errors[] = '有効な日付を選択してください';
    } elseif ($date < new DateTime('today')) {
        $errors[] = '過去の日付は選択できません';
    }
}

// 時間チェック
if (!validateRequired($selected_time)) {
    $errors[] = '時間を選択してください';
} else {
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $selected_time)) {
        $errors[] = '有効な時間を選択してください';
    }
}

// エラーがある場合は予約ページに戻る
if (!empty($errors)) {
    setSessionMessage('errors', $errors);
    setSessionMessage('old_meeting_date', $selected_date . ' ' . $selected_time);
    redirect('/gs_code/gga/page/user/mypage/reserve/new.php');
}

// 日時を結合
$meeting_datetime = $selected_date . ' ' . $selected_time . ':00';

// データベース接続
try {
    $db = getDBConnection();
    
    // 予約総数をチェック（最大3回まで）
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_count FROM reserves 
        WHERE user_id = ?
        AND status IN ('pending', 'confirmed', 'completed')
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_reservations = $result['total_count'];
    
    if ($total_reservations >= 3) {
        setSessionMessage('error', '予約は最大3回までです。既に3回の予約枠を使用しています。');
        redirect('/gs_code/gga/page/user/mypage/reserve/new.php');
    }
    
    // 同じ日時に既に予約があるかチェック
    $stmt = $db->prepare("
        SELECT id FROM reserves 
        WHERE user_id = ? 
        AND meeting_date = ? 
        AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$user_id, $meeting_datetime]);
    
    if ($stmt->fetch()) {
        setSessionMessage('error', '同じ日時に既に予約があります');
        redirect('/gs_code/gga/page/user/mypage/reserve/new.php');
    }
    
    // 予約を作成（trainer_idとpersona_idはNULL）
    $stmt = $db->prepare("
        INSERT INTO reserves (
            user_id, 
            meeting_date, 
            status, 
            created_at, 
            updated_at
        ) VALUES (?, ?, 'pending', NOW(), NOW())
    ");
    $stmt->execute([$user_id, $meeting_datetime]);
    
    // 予約成功
    setSessionMessage('success', '予約リクエストを送信しました。キャリアコンサルタントの承認をお待ちください。');
    redirect('/gs_code/gga/page/user/mypage.php');
    
} catch (PDOException $e) {
    error_log('Reserve Create Error: ' . $e->getMessage());
    setSessionMessage('error', '予約処理中にエラーが発生しました');
    redirect('/gs_code/gga/page/user/mypage/reserve/new.php');
}
