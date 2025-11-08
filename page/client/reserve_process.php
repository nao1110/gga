<?php
/**
 * クライアント（キャリア相談者） 予約処理
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
requireLogin('client');

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/gs_code/gga/page/client/mypage.php');
}

// 現在のユーザー情報取得
$current_user = getCurrentUser();
$client_id = $current_user['id'];

// POSTデータ取得
$trainer_id = $_POST['trainer_id'] ?? '';
$meeting_date = $_POST['meeting_date'] ?? '';
$meeting_time = $_POST['meeting_time'] ?? '';
$consultation_topic = trim($_POST['consultation_topic'] ?? '');

// バリデーション
$errors = [];

// トレーナーIDチェック
if (!validateRequired($trainer_id) || !is_numeric($trainer_id)) {
    $errors[] = 'キャリアコンサルタントIDが不正です';
}

// 日付チェック
if (!validateRequired($meeting_date)) {
    $errors[] = '希望日を選択してください';
} else {
    // 日付が明日以降かチェック
    $selected_date = strtotime($meeting_date);
    $tomorrow = strtotime('tomorrow');
    if ($selected_date < $tomorrow) {
        $errors[] = '予約は明日以降の日付を選択してください';
    }
}

// 時間チェック
if (!validateRequired($meeting_time)) {
    $errors[] = '希望時間を選択してください';
}

// エラーがある場合は予約ページに戻る
if (!empty($errors)) {
    setSessionMessage('errors', $errors);
    redirect("/gs_code/gga/page/client/reserve.php?trainer_id={$trainer_id}");
}

// 日時を結合
$meeting_datetime = $meeting_date . ' ' . $meeting_time . ':00';

// データベース接続
try {
    $db = getDBConnection();
    
    // トレーナーが存在し、有効であることを確認
    $stmt = $db->prepare("SELECT id, name FROM trainers WHERE id = ? AND is_active = 1");
    $stmt->execute([$trainer_id]);
    $trainer = $stmt->fetch();
    
    if (!$trainer) {
        setSessionMessage('error', '指定されたキャリアコンサルタントが見つかりません');
        redirect('/gs_code/gga/page/client/mypage.php');
    }
    
    // 同じ日時に既に予約が入っていないかチェック
    $stmt = $db->prepare("
        SELECT id FROM client_reserves 
        WHERE trainer_id = ? 
        AND meeting_date = ? 
        AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$trainer_id, $meeting_datetime]);
    
    if ($stmt->fetch()) {
        setSessionMessage('error', 'その日時は既に予約が入っています。別の日時を選択してください。');
        redirect("/gs_code/gga/page/client/reserve.php?trainer_id={$trainer_id}");
    }
    
    // 予約を作成
    $stmt = $db->prepare("
        INSERT INTO client_reserves (
            client_id,
            trainer_id,
            meeting_date,
            consultation_topic,
            status,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
    ");
    $stmt->execute([
        $client_id,
        $trainer_id,
        $meeting_datetime,
        $consultation_topic
    ]);
    
    // 成功メッセージを設定してマイページへリダイレクト
    setSessionMessage('success', '予約リクエストを送信しました。キャリアコンサルタントの承認をお待ちください。');
    redirect('/gs_code/gga/page/client/mypage.php');
    
} catch (PDOException $e) {
    error_log('Client Reserve Error: ' . $e->getMessage());
    setSessionMessage('error', '予約処理中にエラーが発生しました');
    redirect("/gs_code/gga/page/client/reserve.php?trainer_id={$trainer_id}");
}
