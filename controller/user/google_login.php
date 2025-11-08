<?php
/**
 * 受験者 Google OAuth ログイン開始
 * 
 * @package CareerTre
 */

// エラー表示を有効化（デバッグ用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// タイムゾーンを日本時間（JST）に設定
date_default_timezone_set('Asia/Tokyo');

try {
    // Composer autoload
    require_once __DIR__ . '/../../vendor/autoload.php';

    // 共通処理読み込み
    require_once __DIR__ . '/../../lib/helpers.php';
    require_once __DIR__ . '/../../lib/google_auth.php';

    // Google Clientの初期化（受験者用）
    $client = getGoogleClientForUser();

    // ログイン用のURLを生成
    $authUrl = $client->createAuthUrl();

    // デバッグ情報（本番環境では削除）
    echo "<!-- Debug: Auth URL generated successfully -->\n";
    echo "<!-- Redirecting to: " . htmlspecialchars($authUrl) . " -->\n";

    // Google認証ページにリダイレクト
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit;
    
} catch (Exception $e) {
    // エラーが発生した場合
    echo "<h1>Google OAuth エラー</h1>";
    echo "<p><strong>エラーメッセージ:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>ファイル:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}
