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
require_once __DIR__ . '/../../lib/google_auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

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
        SELECT r.id, r.status, r.trainer_id, r.user_id, r.persona_id, r.meeting_date,
               u.name as user_name, u.email as user_email, u.google_access_token as user_token,
               t.name as trainer_name, t.email as trainer_email, t.google_access_token as trainer_token,
        (
            SELECT COUNT(*) 
            FROM reserves r2 
            WHERE r2.user_id = r.user_id 
            AND r2.status = 'completed'
            AND r2.meeting_date < r.meeting_date
        ) as completed_count
        FROM reserves r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN trainers t ON t.id = ?
        WHERE r.id = ? AND (r.trainer_id IS NULL OR r.trainer_id = ?)
    ");
    $stmt->execute([$trainer_id, $reserve_id, $trainer_id]);
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
    
    // Google Calendar統合処理
    $meet_url_generated = null;
    error_log("=== Calendar Integration Start ===");
    error_log("Trainer token exists: " . (!empty($reserve['trainer_token']) ? 'YES' : 'NO'));
    error_log("User token exists: " . (!empty($reserve['user_token']) ? 'YES' : 'NO'));
    
    if (!empty($reserve['trainer_token']) && !empty($reserve['user_token'])) {
        error_log("Both tokens available, starting Calendar API integration...");
        try {
            $trainer_token = json_decode($reserve['trainer_token'], true);
            $user_token = json_decode($reserve['user_token'], true);
            error_log("Tokens decoded successfully");
            
            // トレーナーのGoogleクライアント初期化
            $trainerClient = getGoogleClient();
            $trainerClient->setAccessToken($trainer_token);
            error_log("Trainer client initialized");
            
            // トークンの有効期限チェックとリフレッシュ
            if ($trainerClient->isAccessTokenExpired() && $trainerClient->getRefreshToken()) {
                $new_token = $trainerClient->fetchAccessTokenWithRefreshToken($trainerClient->getRefreshToken());
                $trainer_token = array_merge($trainer_token, $new_token);
                $stmt_update = $db->prepare("UPDATE trainers SET google_access_token = ? WHERE id = ?");
                $stmt_update->execute([json_encode($trainer_token), $trainer_id]);
                $trainerClient->setAccessToken($trainer_token);
            }
            
            // ユーザーのGoogleクライアント初期化
            $userClient = getGoogleClientForUser();
            $userClient->setAccessToken($user_token);
            
            // トークンの有効期限チェックとリフレッシュ
            if ($userClient->isAccessTokenExpired() && $userClient->getRefreshToken()) {
                $new_token = $userClient->fetchAccessTokenWithRefreshToken($userClient->getRefreshToken());
                $user_token = array_merge($user_token, $new_token);
                $stmt_update = $db->prepare("UPDATE users SET google_access_token = ? WHERE id = ?");
                $stmt_update->execute([json_encode($user_token), $reserve['user_id']]);
                $userClient->setAccessToken($user_token);
            }
            
            // Calendar API サービスの初期化
            $trainerCalendarService = new Google\Service\Calendar($trainerClient);
            error_log("Calendar service created");
            
            // イベントの開始・終了時刻を設定（1時間の練習）
            $meeting_datetime = new DateTime($reserve['meeting_date'], new DateTimeZone('Asia/Tokyo'));
            $end_datetime = clone $meeting_datetime;
            $end_datetime->modify('+1 hour');
            error_log("Meeting time: " . $meeting_datetime->format('c'));
            
            // ペルソナ情報を取得
            $stmt_persona = $db->prepare("SELECT persona_name, situation FROM personas WHERE id = ?");
            $stmt_persona->execute([$persona_id]);
            $persona = $stmt_persona->fetch();
            
            $persona_info = $persona ? "ペルソナ: " . $persona['persona_name'] . "\n状況: " . $persona['situation'] : "";
            
            // Google Calendar イベントの作成
            $event = new Google\Service\Calendar\Event([
                'summary' => 'キャリアコンサルタント実技練習 - ' . $reserve['user_name'] . ' & ' . $reserve['trainer_name'],
                'description' => "実技練習セッション\n\n受験者: " . $reserve['user_name'] . "\nトレーナー: " . $reserve['trainer_name'] . "\n\n" . $persona_info,
                'start' => [
                    'dateTime' => $meeting_datetime->format('c'),
                    'timeZone' => 'Asia/Tokyo',
                ],
                'end' => [
                    'dateTime' => $end_datetime->format('c'),
                    'timeZone' => 'Asia/Tokyo',
                ],
                'attendees' => [
                    ['email' => $reserve['trainer_email']],
                    ['email' => $reserve['user_email']],
                ],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => uniqid('meet_', true),
                        'conferenceSolutionKey' => [
                            'type' => 'hangoutsMeet'
                        ],
                    ],
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60], // 1日前
                        ['method' => 'popup', 'minutes' => 30], // 30分前
                    ],
                ],
            ]);
            
            error_log("About to insert event to Calendar API...");
            
            // トレーナーのカレンダーにイベントを追加
            $createdEvent = $trainerCalendarService->events->insert('primary', $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all', // 全参加者に通知
            ]);
            
            error_log("Event inserted successfully to trainer's calendar");
            
            // ユーザーのカレンダーにも同じイベントを追加
            $userCalendarService = new Google\Service\Calendar($userClient);
            $userCalendarService->events->insert('primary', $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all',
            ]);
            
            error_log("Event inserted successfully to user's calendar");
            
            // Google Meet URLを取得
            $meet_url_generated = $createdEvent->getHangoutLink();
            
            error_log("Calendar event created. Meet URL: " . ($meet_url_generated ?: 'NULL'));
            
        } catch (Google\Service\Exception $e) {
            error_log('Google Calendar API Error: ' . $e->getMessage());
            error_log('API Error Details: ' . print_r($e->getErrors(), true));
        } catch (Exception $e) {
            error_log('Calendar Integration Error: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
        }
    } else {
        error_log("Skipping Calendar integration - tokens not available");
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
    $stmt->execute([$trainer_id, $persona_id, $meet_url_generated ?: $meeting_url ?: null, $reserve_id]);
    
    // 承認成功
    if ($meet_url_generated) {
        setSessionMessage('success', '予約を承認しました。Google MeetのURLが生成されました');
    } else {
        setSessionMessage('success', '予約を承認しました');
    }
    redirect('/gs_code/gga/page/trainer/mypage.php');
    
} catch (PDOException $e) {
    error_log('Reserve Approve Error: ' . $e->getMessage());
    setSessionMessage('error', '予約承認処理中にエラーが発生しました');
    redirect('/gs_code/gga/page/trainer/mypage.php');
}
