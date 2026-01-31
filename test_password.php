<?php
require_once __DIR__ . '/lib/database.php';

$email = 'naoko.sato@smile-pj.com';
$test_password = 'naoko1110';

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT id, name, email, password FROM trainers WHERE email = ?");
$stmt->execute([$email]);
$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($trainer) {
    echo "アカウント情報:\n";
    echo "ID: " . $trainer['id'] . "\n";
    echo "名前: " . $trainer['name'] . "\n";
    echo "メール: " . $trainer['email'] . "\n";
    echo "パスワードハッシュ: " . $trainer['password'] . "\n\n";
    
    if (password_verify($test_password, $trainer['password'])) {
        echo "✅ パスワード 'naoko1110' は正しいです！ログインできるはずです。\n";
    } else {
        echo "❌ パスワード 'naoko1110' は間違っています。\n";
        echo "\n別のパスワードを試すか、パスワードをリセットしてください。\n";
    }
} else {
    echo "アカウントが見つかりません。\n";
}
