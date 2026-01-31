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
    
    // 代表アカウント（Google Meet作成用）の情報を取得
    $stmt_admin = $db->prepare("
        SELECT id, name, email, google_access_token 
        FROM trainers 
        WHERE email = 'naoko.s1110@gmail.com'
        LIMIT 1
    ");
    $stmt_admin->execute();
    $admin_account = $stmt_admin->fetch();
    
    // ペルソナIDを決定（未割り当ての場合）
    $persona_id = $reserve['persona_id'];
    if (!$persona_id) {
        // 完了回数に基づいてペルソナを割り当て（1-3をループ）
        $persona_id = ($reserve['completed_count'] % 3) + 1;
    }
    
    // ペルソナ情報を取得
    $stmt_persona = $db->prepare("SELECT persona_name, age, family_structure, job, situation FROM personas WHERE id = ?");
    $stmt_persona->execute([$persona_id]);
    $persona = $stmt_persona->fetch();
    
    // Google Calendar統合処理（管理者アカウントで実行、録画はnaoko.s1110@gmail.comに保存）
    $meet_url_generated = null;
    error_log("=== Calendar Integration Start ===");
    error_log("Admin account exists: " . (!empty($admin_account) ? 'YES' : 'NO'));
    error_log("Admin token exists: " . (!empty($admin_account['google_access_token']) ? 'YES' : 'NO'));
    error_log("User token exists: " . (!empty($reserve['user_token']) ? 'YES' : 'NO'));
    
    // 管理者トークンがあればGoogle Meet URLを生成（管理者が主催者、録画データ蓄積用）
    if (!empty($admin_account['google_access_token'])) {
        error_log("Admin token available, starting Calendar API integration...");
        try {
            $admin_token = json_decode($admin_account['google_access_token'], true);
            error_log("Admin token decoded successfully");
            
            // 管理者のGoogleクライアント初期化
            $adminClient = getGoogleClient();
            $adminClient->setAccessToken($admin_token);
            error_log("Admin client initialized");
            
            // トークンの有効期限チェックとリフレッシュ
            if ($adminClient->isAccessTokenExpired() && $adminClient->getRefreshToken()) {
                $new_token = $adminClient->fetchAccessTokenWithRefreshToken($adminClient->getRefreshToken());
                $admin_token = array_merge($admin_token, $new_token);
                $stmt_update = $db->prepare("UPDATE trainers SET google_access_token = ? WHERE id = ?");
                $stmt_update->execute([json_encode($admin_token), $admin_account['id']]);
                $adminClient->setAccessToken($admin_token);
            }
            
            // Calendar API サービスの初期化
            $adminCalendarService = new Google\Service\Calendar($adminClient);
            error_log("Admin calendar service created");
            
            // イベントの開始・終了時刻を設定（1時間の練習）
            $meeting_datetime = new DateTime($reserve['meeting_date'], new DateTimeZone('Asia/Tokyo'));
            $end_datetime = clone $meeting_datetime;
            $end_datetime->modify('+1 hour');
            error_log("Meeting time: " . $meeting_datetime->format('c'));
            
            // ペルソナ情報を取得
            $persona_info = $persona ? "ペルソナ: " . $persona['persona_name'] . "\n状況: " . $persona['situation'] : "";
            
            // Google Calendar イベントの作成（管理者が主催者、録画データ保存先）
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
                    ['email' => $reserve['trainer_email'], 'responseStatus' => 'accepted', 'organizer' => false],
                    ['email' => $reserve['user_email'], 'responseStatus' => 'accepted', 'organizer' => false],
                ],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => uniqid('meet_', true),
                        'conferenceSolutionKey' => [
                            'type' => 'hangoutsMeet'
                        ],
                    ],
                ],
                'guestsCanModify' => true,
                'guestsCanInviteOthers' => true,
                'guestsCanSeeOtherGuests' => true,
                'anyoneCanAddSelf' => true,
                'visibility' => 'public',
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60], // 1日前
                        ['method' => 'popup', 'minutes' => 30], // 30分前
                    ],
                ],
            ]);
            
            error_log("About to insert event to Admin's Calendar API...");
            
            // 管理者のカレンダーにイベントを追加（管理者が主催者、録画保存先）
            $createdEvent = $adminCalendarService->events->insert('primary', $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all', // 全参加者に通知
            ]);
            
            error_log("Event inserted successfully to admin's calendar");
            
            // Google Meet URLを取得（管理者カレンダーから、録画もここに保存される）
            $meet_url_generated = $createdEvent->getHangoutLink();
            error_log("Meet URL generated: " . ($meet_url_generated ?: 'NULL'));
            
            // sendUpdates='all'により、トレーナーと受験者に自動的にカレンダー招待が送信される
            // 招待された人のカレンダーには自動的に表示されるため、個別にイベント追加は不要
            error_log("Calendar invitations sent to trainer and user automatically");
            
            error_log("Calendar event created. Meet URL: " . ($meet_url_generated ?: 'NULL'));
            
        } catch (Google\Service\Exception $e) {
            error_log('Google Calendar API Error: ' . $e->getMessage());
            error_log('API Error Details: ' . print_r($e->getErrors(), true));
        } catch (Exception $e) {
            error_log('Calendar Integration Error: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
        }
    } else {
        error_log("Skipping Calendar integration - admin token not available");
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
    
    // メール通知の送信
    $meeting_datetime = new DateTime($reserve['meeting_date'], new DateTimeZone('Asia/Tokyo'));
    $meeting_date_formatted = $meeting_datetime->format('Y年m月d日（D） H:i');
    $meeting_url_final = $meet_url_generated ?: $meeting_url;
    
    // 受験者へのメール通知
    $to_user = $reserve['user_email'];
    $subject_user = '【キャリアトレーナーズ】実技練習の予約が承認されました';
    $message_user = "{$reserve['user_name']} 様\n\n";
    $message_user .= "実技練習の予約が承認されました。\n\n";
    $message_user .= "■ 実技練習詳細\n";
    $message_user .= "日時: {$meeting_date_formatted}\n";
    $message_user .= "トレーナー: {$reserve['trainer_name']} 様\n\n";
    
    if ($persona) {
        $message_user .= "■ ペルソナ情報（相談者役）\n";
        $message_user .= "名前: {$persona['persona_name']} さん（{$persona['age']}歳）\n";
        $message_user .= "家族構成: {$persona['family_structure']}\n";
        $message_user .= "業種・職種: {$persona['job']}\n";
        $message_user .= "相談内容: {$persona['situation']}\n\n";
    }
    
    if ($meeting_url_final) {
        $message_user .= "■ オンライン会議URL\n";
        $message_user .= "{$meeting_url_final}\n\n";
    }
    
    $message_user .= "準備を整えて、当日をお待ちください。\n";
    $message_user .= "合格を目指して頑張りましょう！\n\n";
    $message_user .= "---\n";
    $message_user .= "キャリアトレーナーズ\n";
    $message_user .= "https://localhost/gs_code/gga/\n";
    
    // トレーナーへのメール通知
    $to_trainer = $reserve['trainer_email'];
    $subject_trainer = '【キャリアトレーナーズ】予約を承認しました';
    $message_trainer = "{$reserve['trainer_name']} 様\n\n";
    $message_trainer .= "以下の実技練習予約を承認しました。\n\n";
    $message_trainer .= "■ 実技練習詳細\n";
    $message_trainer .= "日時: {$meeting_date_formatted}\n";
    $message_trainer .= "受験者: {$reserve['user_name']} 様\n\n";
    
    if ($persona) {
        $message_trainer .= "■ ペルソナ情報（相談者役として演じてください）\n";
        $message_trainer .= "名前: {$persona['persona_name']} さん（{$persona['age']}歳）\n";
        $message_trainer .= "家族構成: {$persona['family_structure']}\n";
        $message_trainer .= "業種・職種: {$persona['job']}\n";
        $message_trainer .= "相談内容: {$persona['situation']}\n\n";
    }
    
    if ($meeting_url_final) {
        $message_trainer .= "■ オンライン会議URL\n";
        $message_trainer .= "{$meeting_url_final}\n\n";
    }
    
    $message_trainer .= "当日はよろしくお願いいたします。\n\n";
    $message_trainer .= "---\n";
    $message_trainer .= "キャリアトレーナーズ\n";
    $message_trainer .= "https://localhost/gs_code/gga/\n";
    
    // メール送信（日本語対応）
    mb_language("japanese");
    mb_internal_encoding("UTF-8");
    
    $headers_user = "From: noreply@career-trainers.com\r\n";
    $headers_user .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    $headers_trainer = "From: noreply@career-trainers.com\r\n";
    $headers_trainer .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // メール送信実行
    $mail_sent_user = mb_send_mail($to_user, $subject_user, $message_user, $headers_user);
    $mail_sent_trainer = mb_send_mail($to_trainer, $subject_trainer, $message_trainer, $headers_trainer);
    
    // ログ出力
    if ($mail_sent_user) {
        error_log("Email sent to user: {$to_user}");
    } else {
        error_log("Failed to send email to user: {$to_user}");
    }
    
    if ($mail_sent_trainer) {
        error_log("Email sent to trainer: {$to_trainer}");
    } else {
        error_log("Failed to send email to trainer: {$to_trainer}");
    }
    
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
