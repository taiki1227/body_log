<?php
declare(strict_types=1);

/**
 * 秘密情報をpublic_html外から読み込みます。
 * BODY_LOG_CONFIG_PATH環境変数がある場合は、そのパスを優先します。
 */

$candidates = [];

$environmentPath = getenv('BODY_LOG_CONFIG_PATH');
if (is_string($environmentPath) && trim($environmentPath) !== '') {
    $candidates[] = trim($environmentPath);
}

$documentRoot = trim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
if ($documentRoot !== '') {
    $candidates[] = dirname(rtrim($documentRoot, '/\\')) . '/private/body-log/config.php';
}

// CoreServerの一般的な構成用フォールバック:
// /virtual/USER/public_html/body-log/app -> /virtual/USER/private/body-log/config.php
$candidates[] = dirname(__DIR__, 3) . '/private/body-log/config.php';

foreach (array_unique($candidates) as $configPath) {
    if (is_file($configPath) && is_readable($configPath)) {
        $config = require $configPath;

        if (!is_array($config)) {
            throw new RuntimeException('Body Logの設定ファイルが配列を返していません。');
        }

        return $config;
    }
}

error_log('[Body Log] private config file was not found.');

http_response_code(500);
echo '設定ファイルを読み込めません。サーバー管理者へお問い合わせください。';
exit;
