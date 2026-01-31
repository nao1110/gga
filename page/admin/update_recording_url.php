<?php
// 管理者専用 録画URL更新処理
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/database.php';
requireLogin('trainer');

// 現在のユーザー情報取得
$current_user = getCurrentUser();

// 管理者チェック（naoko.s1110@gmail.com のみ）
if ($current_user['email'] !== 'naoko.s1110@gmail.com') {
    setSessionMessage('error', '管理者権限がありません');
    redirect('/gs_code/gga/page/admin/dashboard.php');
}

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/gs_code/gga/page/admin/dashboard.php');
}

// POSTデータ取得
$reserve_id = $_POST['reserve_id'] ?? null;
$recording_url = trim($_POST['recording_url'] ?? '');

// バリデーション
if (!$reserve_id || !is_numeric($reserve_id)) {
    setSessionMessage('error', '予約IDが不正です');
    redirect('/gs_code/gga/page/admin/dashboard.php');
}

// URLが入力されている場合は形式チェック
if (!empty($recording_url) && !filter_var($recording_url, FILTER_VALIDATE_URL)) {
    setSessionMessage('error', '有効なURLを入力してください');
    redirect("/gs_code/gga/page/admin/report_detail.php?id={$reserve_id}");
}

try {
    // データベース接続
    $pdo = getDBConnection();
    
    // 予約が存在することを確認
    $stmt = $pdo->prepare("SELECT id FROM reserves WHERE id = ?");
    $stmt->execute([$reserve_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        setSessionMessage('error', '予約が見つかりません');
        redirect('/gs_code/gga/page/admin/dashboard.php');
    }
    
    // 録画URLを更新
    $stmt = $pdo->prepare("
        UPDATE reserves 
        SET recording_url = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$recording_url, $reserve_id]);
    
    setSessionMessage('success', '録画URLを保存しました');
    redirect("/gs_code/gga/page/admin/report_detail.php?id={$reserve_id}");
    
} catch (Exception $e) {
    error_log('Recording URL Update Error: ' . $e->getMessage());
    setSessionMessage('error', '録画URLの保存に失敗しました');
    redirect("/gs_code/gga/page/admin/report_detail.php?id={$reserve_id}");
}
