<?php
/**
 * Client パスワード検証テスト
 */

require_once __DIR__ . '/lib/database.php';

$db = getDBConnection();

$stmt = $db->prepare("SELECT id, name, email, password FROM clients WHERE email = 'yamamoto@example.com'");
$stmt->execute();
$client = $stmt->fetch();

echo "<h2>Client データ</h2>";
echo "<pre>";
print_r($client);
echo "</pre>";

$test_password = 'password123';

echo "<h2>パスワード検証テスト</h2>";
echo "テストパスワード: " . $test_password . "<br>";
echo "DBのハッシュ: " . $client['password'] . "<br>";
echo "password_verify結果: " . (password_verify($test_password, $client['password']) ? 'OK' : 'NG') . "<br>";

// 新しいハッシュを生成
$new_hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "<br><h2>新しいハッシュを生成</h2>";
echo $new_hash . "<br>";
echo "新ハッシュで検証: " . (password_verify($test_password, $new_hash) ? 'OK' : 'NG') . "<br>";
