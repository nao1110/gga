<?php
/**
 * PHPキャッシュクリアスクリプト
 */

// OPcacheをクリア
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully<br>";
} else {
    echo "OPcache is not enabled<br>";
}

// 現在の設定を表示
echo "<h3>Current Settings:</h3>";
echo "error_reporting: " . error_reporting() . "<br>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "log_errors: " . ini_get('log_errors') . "<br>";
echo "error_log: " . ini_get('error_log') . "<br>";

// テストログ出力
error_log("=== Cache clear script executed ===");
echo "<br>Test log written to error log";
