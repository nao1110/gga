<?php
/**
 * トレーナー 予約承認処理
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
    redirect('/gs_code/gga/page/trainer/mypage.php');
}

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$trainer_id = $current_user['id'];

// POSTデータ取得
$reserve_id = $_POST['reserve_id'] ?? '';
$meeting_url = trim($_POST['meeting_url'] ?? '');

// バリデーション
$errors = [];

// 予約IDチェック
if (!validateRequired($reserve_id) || !is_numeric($reserve_id)) {
    $errors[] = '予約IDが不正です';
}

// Meeting URLチェック（任意）
if (!empty($meeting_url)) {
    if (!filter_var($meeting_url, FILTER_VALIDATE_URL)) {
        $errors[] = '有効なURLを入力してください';
    }
}

// エラーがある場合はマイページに戻る
if (!empty($errors)) {
    setSessionMessage('errors', $errors);
    redirect('/gs_code/gga/page/trainer/mypage.php');
}

// データベース接続
try {
    $db = getDBConnection();
    
    // 予約が存在することを確認（trainer_idがNULLまたは自分のID）
    $stmt = $db->prepare("
        SELECT r.id, r.status, r.trainer_id, r.user_id, r.persona_id,
        (
            SELECT COUNT(*) 
            FROM reserves r2 
            WHERE r2.user_id = r.user_id 
            AND r2.status = 'completed'
            AND r2.meeting_date < r.meeting_date
        ) as completed_count
        FROM reserves r
        WHERE r.id = ? AND (r.trainer_id IS NULL OR r.trainer_id = ?)
    ");
    $stmt->execute([$reserve_id, $trainer_id]);
    $reserve = $stmt->fetch();
    
    if (!$reserve) {
        setSessionMessage('error', '予約が見つかりません');
        redirect('/gs_code/gga/page/trainer/mypage.php');
    }
    
    if ($reserve['status'] !== 'pending') {
        setSessionMessage('error', 'この予約は既に処理されています');
        redirect('/gs_code/gga/page/trainer/mypage.php');
    }
    
    // ペルソナIDを決定（未割り当ての場合）
    $persona_id = $reserve['persona_id'];
    if (!$persona_id) {
        // 完了回数に基づいてペルソナを割り当て（1-5をループ）
        $persona_id = ($reserve['completed_count'] % 5) + 1;
    }
    
    // 予約を承認（trainer_idとpersona_idも設定）
    $stmt = $db->prepare("
        UPDATE reserves 
        SET status = 'confirmed',
            trainer_id = ?,
            persona_id = ?,
            meeting_url = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$trainer_id, $persona_id, $meeting_url ?: null, $reserve_id]);
    
    // 承認成功
    setSessionMessage('success', '予約を承認しました');
    redirect('/gs_code/gga/page/trainer/mypage.php');
    
} catch (PDOException $e) {
    error_log('Reserve Approve Error: ' . $e->getMessage());
    setSessionMessage('error', '予約承認処理中にエラーが発生しました');
    redirect('/gs_code/gga/page/trainer/mypage.php');
}
