<?php
/**
 * ヘルパー関数（ユーティリティ）
 * 
 * @package CareerTre
 */

/**
 * リダイレクト
 * 
 * @param string $path リダイレクト先パス
 * @return void
 */
function redirect($path) {
    // 本番環境のベースURLを取得
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $fullUrl = $protocol . '://' . $host . $path;
    
    header("Location: {$fullUrl}");
    exit;
}

/**
 * JSONレスポンス
 * 
 * @param mixed $data データ
 * @param int $status HTTPステータスコード
 * @return void
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * セッションメッセージ取得（取得後に削除）
 * 
 * @param string $key セッションキー
 * @return mixed|null メッセージ、存在しない場合null
 */
function getSessionMessage($key) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    
    return null;
}

/**
 * セッションメッセージをセット
 * 
 * @param string $key セッションキー
 * @param mixed $value 値
 * @return void
 */
function setSessionMessage($key, $value) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION[$key] = $value;
}

/**
 * CSRFトークン生成
 * 
 * @return string トークン
 */
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * CSRFトークン検証
 * 
 * @param string $token 検証するトークン
 * @return bool
 */
function verifyCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 日付フォーマット（日本語）
 * 
 * @param string $datetime 日時文字列
 * @param string $format フォーマット
 * @return string フォーマット済み日時
 */
function formatDate($datetime, $format = 'Y年m月d日 H:i') {
    if (empty($datetime)) {
        return '';
    }
    
    $date = new DateTime($datetime);
    return $date->format($format);
}
