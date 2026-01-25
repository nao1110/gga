<?php
/**
 * 認証・セッション管理
 * 
 * @package CareerTre
 */

/**
 * ログイン状態チェック（保護されたページで使用）
 * 
 * @param string $user_type ユーザータイプ（'user', 'trainer', 'client'）
 * @return void ログインしていない場合はリダイレクト
 */
function requireLogin($user_type = 'user') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== $user_type) {
        $_SESSION['error'] = 'ログインが必要です';
        header('Location: /gs_code/gga/page/' . $user_type . '/login.php');
        exit;
    }
}

/**
 * ログイン処理
 * 
 * @param int $user_id ユーザーID
 * @param string $user_type ユーザータイプ（'user', 'trainer', 'client'）
 * @param string $name ユーザー名
 * @param string $email メールアドレス
 * @return void
 */
function loginUser($user_id, $user_type, $name, $email) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // セッション固定攻撃対策
    session_regenerate_id(true);
    
    // セッションに保存
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['login_time'] = time();
}

/**
 * ログアウト処理
 * 
 * @return void
 */
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // セッション変数をクリア
    $_SESSION = [];
    
    // セッションCookieを削除
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // セッションを破棄
    session_destroy();
}

/**
 * ログイン中のユーザー情報を取得
 * 
 * @return array|null ユーザー情報配列、未ログインの場合null
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'type' => $_SESSION['user_type'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
        ];
    }
    
    return null;
}

/**
 * ログイン状態チェック（boolean）
 * 
 * @return bool
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']);
}
