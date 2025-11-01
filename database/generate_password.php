<?php
/**
 * パスワードハッシュ生成ツール
 * 
 * 使い方:
 * php database/generate_password.php password123
 */

if ($argc < 2) {
    echo "使い方: php generate_password.php <パスワード>\n";
    echo "例: php generate_password.php password123\n";
    exit(1);
}

$password = $argv[1];
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "\n================================\n";
echo "パスワード: {$password}\n";
echo "ハッシュ値:\n";
echo "{$hash}\n";
echo "================================\n\n";

echo "SQLで使う場合:\n";
echo "UPDATE users SET password = '{$hash}' WHERE email = 'yamada@example.com';\n\n";
?>
