<?php
/**
 * 受験者 自己フィードバック保存処理
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
    redirect('/gs_code/gga/page/user/mypage.php');
}

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$user_id = $current_user['id'];

// POSTデータ取得
$reserve_id = isset($_POST['reserve_id']) ? intval($_POST['reserve_id']) : 0;
$satisfaction = isset($_POST['satisfaction']) ? intval($_POST['satisfaction']) : 0;
$strengths = trim($_POST['strengths'] ?? '');
$challenges = trim($_POST['challenges'] ?? '');
$learnings = trim($_POST['learnings'] ?? '');
$next_goals = trim($_POST['next_goals'] ?? '');

// バリデーション
$errors = [];

if (!$reserve_id) {
    $errors[] = '予約IDが不正です';
}

if ($satisfaction < 1 || $satisfaction > 5) {
    $errors[] = '満足度を1〜5の範囲で選択してください';
}

if (!validateRequired($strengths)) {
    $errors[] = '良かった点を入力してください';
}

if (!validateRequired($challenges)) {
    $errors[] = '改善が必要な点を入力してください';
}

if (!validateRequired($learnings)) {
    $errors[] = '気づき・学びを入力してください';
}

if (!validateRequired($next_goals)) {
    $errors[] = '次回の目標を入力してください';
}

// エラーがある場合は戻る
if (!empty($errors)) {
    setSessionMessage('errors', $errors);
    redirect('/gs_code/gga/page/user/mypage/reserve/feedback/view.php?id=' . $reserve_id);
}

// データベース接続
try {
    $db = getDBConnection();
    
    // 予約が自分のものか確認
    $stmt = $db->prepare("
        SELECT id, user_id, status 
        FROM reserves 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$reserve_id, $user_id]);
    $reserve = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserve) {
        setSessionMessage('error', '予約が見つかりません');
        redirect('/gs_code/gga/page/user/mypage.php');
    }
    
    // フィードバックデータをJSONで構築
    $feedback_data = [
        'satisfaction' => $satisfaction,
        'strengths' => $strengths,
        'challenges' => $challenges,
        'learnings' => $learnings,
        'next_goals' => $next_goals
    ];
    
    $comment_json = json_encode($feedback_data, JSON_UNESCAPED_UNICODE);
    
    // 既存のレポートがあるか確認
    $stmt = $db->prepare("SELECT id FROM reports WHERE reserve_id = ?");
    $stmt->execute([$reserve_id]);
    $existing_report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_report) {
        // 更新
        $stmt = $db->prepare("
            UPDATE reports 
            SET comment = ?, updated_at = NOW()
            WHERE reserve_id = ?
        ");
        $stmt->execute([$comment_json, $reserve_id]);
    } else {
        // 新規作成
        $stmt = $db->prepare("
            INSERT INTO reports (
                reserve_id, 
                user_id, 
                comment, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$reserve_id, $user_id, $comment_json]);
    }
    
    // 成功
    setSessionMessage('success', '自己フィードバックを保存しました');
    redirect('/gs_code/gga/page/user/mypage/reserve/feedback/view.php?id=' . $reserve_id);
    
} catch (PDOException $e) {
    error_log('Self Feedback Save Error: ' . $e->getMessage());
    setSessionMessage('error', 'フィードバック保存中にエラーが発生しました');
    redirect('/gs_code/gga/page/user/mypage/reserve/feedback/view.php?id=' . $reserve_id);
}
