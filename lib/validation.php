<?php
/**
 * バリデーション関数
 * 
 * @package CareerTre
 */

/**
 * メールアドレス検証
 * 
 * @param string $email メールアドレス
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * パスワード強度検証
 * 
 * @param string $password パスワード
 * @param int $min_length 最小文字数（デフォルト8）
 * @return bool
 */
function validatePassword($password, $min_length = 8) {
    return strlen($password) >= $min_length;
}

/**
 * 必須項目チェック
 * 
 * @param string $value 値
 * @return bool
 */
function validateRequired($value) {
    return !empty(trim($value));
}

/**
 * XSS対策（エスケープ）
 * 
 * @param string $str 文字列
 * @return string エスケープ済み文字列
 */
function h($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 文字列長チェック
 * 
 * @param string $value 値
 * @param int $min 最小文字数
 * @param int $max 最大文字数
 * @return bool
 */
function validateLength($value, $min = 0, $max = PHP_INT_MAX) {
    $length = mb_strlen($value, 'UTF-8');
    return $length >= $min && $length <= $max;
}
