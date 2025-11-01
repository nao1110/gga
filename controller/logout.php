<?php
/**
 * ログアウト処理
 * 
 * @package CareerTre
 */

// 共通処理読み込み
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

// セッション開始
session_start();

// ユーザータイプ取得（リダイレクト先決定用）
$user_type = $_SESSION['user_type'] ?? 'user';

// ログアウト処理
logoutUser();

// 成功メッセージをセット
setSessionMessage('success', 'ログアウトしました');

// ログインページにリダイレクト
if ($user_type === 'trainer') {
    redirect('/gs_code/gga/page/trainer/login.php');
} else {
    redirect('/gs_code/gga/page/user/login.php');
}
