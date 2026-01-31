<?php
/**
 * データベース接続処理（本番環境用）
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
    // 本番環境の設定（さくらインターネット用）
    $host = 'mysql80.gsacademy.sakura.ne.jp';  // データベースサーバー名
    $dbname = 'gsacademy_careertrainers';       // データベース名
    $username = 'gsacademy_careertrainers';     // データベースユーザー名
    $password = 'naoko1110';                    // データベースパスワード
    
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
        error_log('Database Connection Error: ' . $e->getMessage());
        throw $e;
    }
}
