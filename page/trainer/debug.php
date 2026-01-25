<?php
session_start();
echo "<h1>セッション情報</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

require_once __DIR__ . '/../../lib/database.php';

if (isset($_SESSION['user_id'])) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id, name, email FROM trainers WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $trainer = $stmt->fetch();
    
    echo "<h2>トレーナー情報</h2>";
    echo "<pre>";
    print_r($trainer);
    echo "</pre>";
}

// client_reserves確認
$db = getDBConnection();
$stmt = $db->query("SELECT * FROM client_reserves");
$reserves = $stmt->fetchAll();
echo "<h2>client_reserves テーブル</h2>";
echo "<pre>";
print_r($reserves);
echo "</pre>";
?>
