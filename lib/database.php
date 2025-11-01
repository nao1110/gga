<?php
/**
 * データベース接続処理
 * 
 * @package CareerTre
 */

/**
 * データベース接続を取得
 * 
 * @return PDO PDOインスタンス
 * @throws PDOException 接続失敗時
 */
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'careertre_db';
    $username = 'root';
    $password = '';
    
    try {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        
        // MySQLのタイムゾーンを日本時間（JST）に設定
        $pdo->exec("SET time_zone = '+09:00'");
        
        return $pdo;
        
    } catch (PDOException $e) {
        // 本番環境では詳細なエラーを表示しない
        error_log('Database Connection Error: ' . $e->getMessage());
        die('データベース接続エラーが発生しました。管理者にお問い合わせください。');
    }
}
