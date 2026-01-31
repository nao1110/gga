<?php
/**
 * トレーナー フィードバック登録・更新処理
 * 
 * @package CareerTre
 */

// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

// 共通処理読み込み
require_once __DIR__ . '/../../../../../lib/database.php';
require_once __DIR__ . '/../../../../../lib/auth.php';
require_once __DIR__ . '/../../../../../lib/validation.php';
require_once __DIR__ . '/../../../../../lib/helpers.php';

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
$reservation_id = $_POST['reservation_id'] ?? '';
$attitude_comment = trim($_POST['attitude_comment'] ?? '');
$attitude_score_1 = intval($_POST['attitude_score_1'] ?? 0);
$attitude_score_2 = intval($_POST['attitude_score_2'] ?? 0);
$problem_comment = trim($_POST['problem_comment'] ?? '');
$problem_score_1 = intval($_POST['problem_score_1'] ?? 0);
$problem_score_2 = intval($_POST['problem_score_2'] ?? 0);
$development_comment = trim($_POST['development_comment'] ?? '');
$development_score_1 = intval($_POST['development_score_1'] ?? 0);
$development_score_2 = intval($_POST['development_score_2'] ?? 0);
$next_advice = trim($_POST['next_advice'] ?? '');

// バリデーション
$errors = [];

// 予約IDチェック
if (!validateRequired($reservation_id) || !is_numeric($reservation_id)) {
    $errors[] = '予約IDが不正です';
}

// 態度・傾聴のフィードバックチェック
if (!validateRequired($attitude_comment)) {
    $errors[] = '態度・傾聴（基本的姿勢）のフィードバックを入力してください';
}
if ($attitude_score_1 < 1 || $attitude_score_1 > 5) {
    $errors[] = '態度・傾聴のチェックポイント1のスコアを選択してください';
}
if ($attitude_score_2 < 1 || $attitude_score_2 > 5) {
    $errors[] = '態度・傾聴のチェックポイント2のスコアを選択してください';
}

// 問題把握のフィードバックチェック
if (!validateRequired($problem_comment)) {
    $errors[] = '問題把握のフィードバックを入力してください';
}
if ($problem_score_1 < 1 || $problem_score_1 > 5) {
    $errors[] = '問題把握のチェックポイント1のスコアを選択してください';
}
if ($problem_score_2 < 1 || $problem_score_2 > 5) {
    $errors[] = '問題把握のチェックポイント2のスコアを選択してください';
}

// 具体的展開のフィードバックチェック
if (!validateRequired($development_comment)) {
    $errors[] = '具体的展開のフィードバックを入力してください';
}
if ($development_score_1 < 1 || $development_score_1 > 5) {
    $errors[] = '具体的展開のチェックポイント1のスコアを選択してください';
}
if ($development_score_2 < 1 || $development_score_2 > 5) {
    $errors[] = '具体的展開のチェックポイント2のスコアを選択してください';
}

// 次回へのアドバイスチェック
if (!validateRequired($next_advice)) {
    $errors[] = '次回の面談に向けたアドバイスを入力してください';
}

// エラーがある場合は入力ページに戻る
if (!empty($errors)) {
    setSessionMessage('errors', $errors);
    redirect("/gs_code/gga/page/trainer/mypage/reserve/feedback/input.php?id={$reservation_id}");
}

// フィードバックデータをJSON形式で作成
$feedback_json = json_encode([
    'attitude_comment' => $attitude_comment,
    'attitude_score_1' => $attitude_score_1,
    'attitude_score_2' => $attitude_score_2,
    'problem_comment' => $problem_comment,
    'problem_score_1' => $problem_score_1,
    'problem_score_2' => $problem_score_2,
    'development_comment' => $development_comment,
    'development_score_1' => $development_score_1,
    'development_score_2' => $development_score_2,
    'next_advice' => $next_advice
], JSON_UNESCAPED_UNICODE);

// データベース接続
try {
    $db = getDBConnection();
    
    // 予約が存在し、自分の予約であることを確認
    $stmt = $db->prepare("
        SELECT id, status 
        FROM reserves 
        WHERE id = ? AND trainer_id = ?
    ");
    $stmt->execute([$reservation_id, $trainer_id]);
    $reserve = $stmt->fetch();
    
    if (!$reserve) {
        setSessionMessage('error', '予約が見つかりません');
        redirect('/gs_code/gga/page/trainer/mypage.php');
    }
    
    // 既存のフィードバックがあるかチェック
    $stmt = $db->prepare("
        SELECT id FROM feedbacks 
        WHERE reserve_id = ?
    ");
    $stmt->execute([$reservation_id]);
    $existing_feedback = $stmt->fetch();
    
    if ($existing_feedback) {
        // 既存のフィードバックを更新
        $stmt = $db->prepare("
            UPDATE feedbacks 
            SET comment = ?,
                updated_at = NOW()
            WHERE reserve_id = ?
        ");
        $stmt->execute([$feedback_json, $reservation_id]);
        
        // 予約のステータスもcompletedに更新（念のため）
        $stmt = $db->prepare("
            UPDATE reserves 
            SET status = 'completed',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reservation_id]);
        
        $success_message = 'フィードバックを更新しました';
    } else {
        // 新規フィードバックを作成
        $stmt = $db->prepare("
            INSERT INTO feedbacks (
                reserve_id,
                trainer_id,
                comment,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$reservation_id, $trainer_id, $feedback_json]);
        $success_message = 'フィードバックを提出しました';
        
        // 予約のステータスをcompletedに更新
        $stmt = $db->prepare("
            UPDATE reserves 
            SET status = 'completed',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$reservation_id]);
    }
    
    // 成功メッセージを設定してマイページへリダイレクト
    setSessionMessage('success', $success_message);
    redirect('/gs_code/gga/page/trainer/mypage.php');
    
} catch (PDOException $e) {
    error_log('Feedback Process Error: ' . $e->getMessage());
    setSessionMessage('error', 'フィードバック処理中にエラーが発生しました');
    redirect("/gs_code/gga/page/trainer/mypage/reserve/feedback/input.php?id={$reservation_id}");
}
