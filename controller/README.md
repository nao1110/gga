# Controller フォルダ

## 役割
CRUDの**表示以外の処理**を担当するコントローラー層です。

## 責務
- ✅ Create（新規作成）処理
- ✅ Update（更新）処理
- ✅ Delete（削除）処理
- ✅ バリデーション
- ✅ ビジネスロジック
- ✅ リダイレクト処理
- ❌ HTML表示（page/フォルダの責務）

## ファイル命名規則
- 処理の目的を明確にする
- 例: `login_process.php`, `register_process.php`, `reserve_create.php`

## ディレクトリ構造
```
controller/
├── user/                    # 受験者用コントローラー
│   ├── login_process.php
│   ├── register_process.php
│   ├── reserve_create.php
│   └── feedback_submit.php
└── trainer/                 # トレーナー用コントローラー
    ├── login_process.php
    ├── register_process.php
    ├── reserve_approve.php
    └── feedback_submit.php
```

## 実装パターン例

```php
<?php
// controller/user/register_process.php

session_start();
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/validation.php';

// POSTデータ取得
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// バリデーション
$errors = [];
if (empty($name)) $errors[] = '名前を入力してください';
if (empty($email)) $errors[] = 'メールアドレスを入力してください';
if (empty($password)) $errors[] = 'パスワードを入力してください';

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: ../../page/user/register.php');
    exit;
}

// DB処理
try {
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt->execute([$name, $email, $hashed_password]);
    
    $_SESSION['success'] = '登録が完了しました';
    header('Location: ../../page/user/login.php');
} catch (Exception $e) {
    $_SESSION['error'] = '登録に失敗しました';
    header('Location: ../../page/user/register.php');
}
exit;
```

## 注意事項
1. **処理後は必ずリダイレクト**: PRGパターン（Post-Redirect-Get）を実装
2. **直接HTML出力しない**: 表示はpage/フォルダに任せる
3. **セッションでメッセージ伝達**: 成功・エラーメッセージはセッション経由
4. **セキュリティ対策必須**: SQLインジェクション、XSS対策を実装
