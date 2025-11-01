# Lib フォルダ

## 役割
アプリケーション全体で使用する**共通処理**を集約します。

## 責務
- ✅ データベース接続
- ✅ セッション管理
- ✅ バリデーション関数
- ✅ ユーティリティ関数
- ✅ 認証・認可処理
- ✅ メール送信処理
- ✅ ファイルアップロード処理

## ファイル一覧

### database.php
データベース接続処理

```php
<?php
/**
 * データベース接続を取得
 * @return PDO
 */
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'careertre_db';
    $username = 'root';
    $password = '';
    
    try {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        die('データベース接続エラー');
    }
}
```

### auth.php
認証・セッション管理

```php
<?php
/**
 * ログイン状態チェック
 */
function requireLogin($user_type = 'user') {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== $user_type) {
        header('Location: /gs_code/gga/page/' . $user_type . '/login.php');
        exit;
    }
}

/**
 * ログイン処理
 */
function loginUser($user_id, $user_type, $name) {
    session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['user_name'] = $name;
}

/**
 * ログアウト処理
 */
function logoutUser() {
    session_start();
    $_SESSION = [];
    session_destroy();
}
```

### validation.php
バリデーション関数

```php
<?php
/**
 * メールアドレス検証
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * パスワード強度検証
 */
function validatePassword($password) {
    return strlen($password) >= 8;
}

/**
 * XSS対策（エスケープ）
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
```

### helpers.php
ユーティリティ関数

```php
<?php
/**
 * リダイレクト
 */
function redirect($path) {
    header("Location: {$path}");
    exit;
}

/**
 * JSONレスポンス
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * エラーメッセージ取得
 */
function getSessionMessage($key) {
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}
```

## 使用例

```php
<?php
// コントローラーで共通処理を使用

require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/helpers.php';

// ログインチェック
requireLogin('user');

// データベース接続
$db = getDBConnection();

// バリデーション
if (!validateEmail($email)) {
    redirect('/page/user/register.php?error=invalid_email');
}

// XSSエスケープ
echo h($user_name);
```

## セキュリティ推奨事項
1. **環境変数の使用**: DB接続情報は環境変数で管理
2. **プリペアドステートメント**: SQL実行は必ずプリペアドステートメント使用
3. **パスワードハッシュ**: password_hash() / password_verify() 使用
4. **セッション固定攻撃対策**: ログイン時にsession_regenerate_id()
5. **CSRF対策**: トークン生成・検証機能の実装
