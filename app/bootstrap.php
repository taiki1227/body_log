<?php
declare(strict_types=1);

function app_config(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $configPath = dirname(__DIR__, 3)
        . '/private/body-log/config.php';

    if (!is_file($configPath) || !is_readable($configPath)) {
        error_log(
            '[Body Log] config.phpを読み込めません: '
            . $configPath
        );

        http_response_code(500);
        echo '設定ファイルを読み込めません。';
        exit;
    }

    $loadedConfig = require $configPath;

    if (!is_array($loadedConfig)) {
        error_log(
            '[Body Log] config.phpが配列を返していません。'
        );

        http_response_code(500);
        echo '設定ファイルが正しくありません。';
        exit;
    }

    $config = $loadedConfig;

    return $config;
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_name']
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function ensure_schema(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo = db();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NULL,
            password_hash VARCHAR(255) NOT NULL,
            profile_sex VARCHAR(10) NULL,
            profile_age TINYINT UNSIGNED NULL,
            profile_height_cm DECIMAL(5,1) NULL,
            profile_updated_at TIMESTAMP NULL DEFAULT NULL,
            reset_token_hash CHAR(64) NULL,
            reset_token_expires_at DATETIME NULL,
            reset_requested_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_users_email (email),
            INDEX idx_users_reset_token_hash (reset_token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            log_date DATE NOT NULL,
            weight_kg DECIMAL(5,1) NOT NULL,
            calories INT UNSIGNED NULL,
            steps INT UNSIGNED NULL,
            memo TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_date (user_id, log_date),
            INDEX idx_user_date (user_id, log_date),
            CONSTRAINT fk_logs_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    ensure_users_schema($pdo);
    ensure_logs_schema($pdo);

    $done = true;
}

function get_column_info(PDO $pdo, string $table, string $column): ?array
{
    $stmt = $pdo->prepare("
        SELECT IS_NULLABLE, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
          AND COLUMN_NAME = :column_name
        LIMIT 1
    ");

    $stmt->execute([
        ':table_name' => $table,
        ':column_name' => $column,
    ]);

    $result = $stmt->fetch();

    return $result ?: null;
}

function index_exists(PDO $pdo, string $table, string $indexName): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
          AND INDEX_NAME = :index_name
        LIMIT 1
    ");

    $stmt->execute([
        ':table_name' => $table,
        ':index_name' => $indexName,
    ]);

    return (bool)$stmt->fetchColumn();
}

function ensure_users_schema(PDO $pdo): void
{
    if (get_column_info($pdo, 'users', 'email') === null) {
        $pdo->exec("ALTER TABLE users ADD email VARCHAR(255) NULL AFTER username");
    }

    if (get_column_info($pdo, 'users', 'profile_sex') === null) {
        $pdo->exec("ALTER TABLE users ADD profile_sex VARCHAR(10) NULL AFTER password_hash");
    }

    if (get_column_info($pdo, 'users', 'profile_age') === null) {
        $pdo->exec("ALTER TABLE users ADD profile_age TINYINT UNSIGNED NULL AFTER profile_sex");
    }

    if (get_column_info($pdo, 'users', 'profile_height_cm') === null) {
        $pdo->exec("ALTER TABLE users ADD profile_height_cm DECIMAL(5,1) NULL AFTER profile_age");
    }

    if (get_column_info($pdo, 'users', 'profile_updated_at') === null) {
        $pdo->exec("ALTER TABLE users ADD profile_updated_at TIMESTAMP NULL DEFAULT NULL AFTER profile_height_cm");
    }

    if (get_column_info($pdo, 'users', 'reset_token_hash') === null) {
        $pdo->exec("ALTER TABLE users ADD reset_token_hash CHAR(64) NULL AFTER profile_updated_at");
    }

    if (get_column_info($pdo, 'users', 'reset_token_expires_at') === null) {
        $pdo->exec("ALTER TABLE users ADD reset_token_expires_at DATETIME NULL AFTER reset_token_hash");
    }

    if (get_column_info($pdo, 'users', 'reset_requested_at') === null) {
        $pdo->exec("ALTER TABLE users ADD reset_requested_at TIMESTAMP NULL DEFAULT NULL AFTER reset_token_expires_at");
    }

    if (!index_exists($pdo, 'users', 'idx_users_email')) {
        $pdo->exec("CREATE INDEX idx_users_email ON users (email)");
    }

    if (!index_exists($pdo, 'users', 'idx_users_reset_token_hash')) {
        $pdo->exec("CREATE INDEX idx_users_reset_token_hash ON users (reset_token_hash)");
    }
}

function ensure_logs_schema(PDO $pdo): void
{
    $caloriesInfo = get_column_info($pdo, 'logs', 'calories');

    if ($caloriesInfo && strtoupper((string)$caloriesInfo['IS_NULLABLE']) !== 'YES') {
        $pdo->exec("ALTER TABLE logs MODIFY calories INT UNSIGNED NULL");
    }

    if (get_column_info($pdo, 'logs', 'steps') === null) {
        $pdo->exec("ALTER TABLE logs ADD steps INT UNSIGNED NULL AFTER calories");
    }

    if (get_column_info($pdo, 'logs', 'memo') === null) {
        $pdo->exec("ALTER TABLE logs ADD memo TEXT NULL AFTER steps");
    }
}

function user_count(): int
{
    ensure_schema();
    return (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

function registration_available(): bool
{
    $config = app_config();
    $count = user_count();

    if ($count === 0) {
        return true;
    }

    return (bool)($config['allow_registration'] ?? false);
}

function normalize_cookie_path(string $path): string
{
    $path = trim($path);

    if ($path === '' || $path[0] !== '/') {
        return '/body-log/';
    }

    return substr($path, -1) === '/' ? $path : $path . '/';
}

function is_https_request(): bool
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $config = app_config();
    session_name((string)($config['session_name'] ?? 'tkgstudio_body_log_session'));

    $cookiePath = normalize_cookie_path(
        (string)($config['session_cookie_path'] ?? '/body-log/')
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function current_user_id(): ?int
{
    start_app_session();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function require_login(): int
{
    ensure_schema();

    $userId = current_user_id();

    if (!$userId) {
        header('Location: login.php');
        exit;
    }

    return $userId;
}

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function is_valid_email(string $email): bool
{
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function get_user_account(int $userId): array
{
    ensure_schema();

    $stmt = db()->prepare("
        SELECT username, email, profile_sex, profile_age, profile_height_cm
        FROM users
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch() ?: [];

    $sex = (string)($row['profile_sex'] ?? '');
    if (!in_array($sex, ['male', 'female'], true)) {
        $sex = '';
    }

    $age = isset($row['profile_age']) && $row['profile_age'] !== null ? (int)$row['profile_age'] : null;
    $heightCm = isset($row['profile_height_cm']) && $row['profile_height_cm'] !== null ? (float)$row['profile_height_cm'] : null;
    $email = isset($row['email']) && $row['email'] !== null ? (string)$row['email'] : '';

    return [
        'username' => (string)($row['username'] ?? ''),
        'email' => $email,
        'sex' => $sex,
        'age' => $age,
        'height_cm' => $heightCm,
        'is_profile_complete' => $sex !== '' && $age !== null && $heightCm !== null,
        'has_email' => $email !== '',
    ];
}

function get_user_profile(int $userId): array
{
    $account = get_user_account($userId);

    return [
        'sex' => $account['sex'],
        'age' => $account['age'],
        'height_cm' => $account['height_cm'],
        'is_complete' => $account['is_profile_complete'],
    ];
}

function email_is_used_by_other_user(int $userId, string $email): bool
{
    ensure_schema();

    $stmt = db()->prepare("SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1");
    $stmt->execute([
        ':email' => $email,
        ':id' => $userId,
    ]);

    return (bool)$stmt->fetchColumn();
}

function username_is_used_by_other_user(int $userId, string $username): bool
{
    ensure_schema();

    $stmt = db()->prepare("SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1");
    $stmt->execute([
        ':username' => $username,
        ':id' => $userId,
    ]);

    return (bool)$stmt->fetchColumn();
}

function save_user_profile(int $userId, string $sex, int $age, float $heightCm): void
{
    ensure_schema();

    $stmt = db()->prepare("
        UPDATE users
        SET
            profile_sex = :profile_sex,
            profile_age = :profile_age,
            profile_height_cm = :profile_height_cm,
            profile_updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $userId,
        ':profile_sex' => $sex,
        ':profile_age' => $age,
        ':profile_height_cm' => $heightCm,
    ]);
}

function save_user_account_settings(int $userId, string $username, ?string $email, string $sex, int $age, float $heightCm): void
{
    ensure_schema();

    $stmt = db()->prepare("
        UPDATE users
        SET
            username = :username,
            email = :email,
            profile_sex = :profile_sex,
            profile_age = :profile_age,
            profile_height_cm = :profile_height_cm,
            profile_updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $userId,
        ':username' => $username,
        ':email' => $email,
        ':profile_sex' => $sex,
        ':profile_age' => $age,
        ':profile_height_cm' => $heightCm,
    ]);
}

function create_password_reset_token(int $userId): string
{
    ensure_schema();

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

    $stmt = db()->prepare("
        UPDATE users
        SET
            reset_token_hash = :reset_token_hash,
            reset_token_expires_at = :reset_token_expires_at,
            reset_requested_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmt->execute([
        ':id' => $userId,
        ':reset_token_hash' => $tokenHash,
        ':reset_token_expires_at' => $expiresAt,
    ]);

    return $token;
}

function find_user_by_email(string $email): ?array
{
    ensure_schema();

    $stmt = db()->prepare("SELECT id, username, email FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function find_user_by_reset_token(string $token): ?array
{
    ensure_schema();

    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    $tokenHash = hash('sha256', $token);
    $stmt = db()->prepare("
        SELECT id, username, reset_token_expires_at
        FROM users
        WHERE reset_token_hash = :reset_token_hash
        LIMIT 1
    ");
    $stmt->execute([':reset_token_hash' => $tokenHash]);
    $user = $stmt->fetch();

    if (!$user || empty($user['reset_token_expires_at'])) {
        return null;
    }

    $expiresAt = new DateTimeImmutable((string)$user['reset_token_expires_at']);
    $now = new DateTimeImmutable('now');

    if ($expiresAt < $now) {
        return null;
    }

    return $user;
}

function reset_user_password(int $userId, string $password): void
{
    ensure_schema();

    $stmt = db()->prepare("
        UPDATE users
        SET
            password_hash = :password_hash,
            reset_token_hash = NULL,
            reset_token_expires_at = NULL,
            reset_requested_at = NULL
        WHERE id = :id
    ");
    $stmt->execute([
        ':id' => $userId,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
}

function app_url(string $path = '', array $params = []): string
{
    $config = app_config();
    $baseUrl = rtrim((string)($config['app_url'] ?? ''), '/');

    if ($baseUrl === '') {
        $scheme = is_https_request() ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
        $scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
        $baseUrl = $scheme . '://' . $host . $scriptDir;
    }

    $url = $path === ''
        ? $baseUrl . '/'
        : $baseUrl . '/' . ltrim($path, '/');

    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

function mime_header(string $value): string
{
    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function app_mail_from(): array
{
    $config = app_config();

    return [
        'email' => (string)($config['mail_from'] ?? 'no-reply@example.com'),
        'name' => (string)($config['mail_from_name'] ?? ($config['app_name'] ?? '体重・カロリー記録')),
    ];
}

function send_app_mail(string $to, string $subject, string $body): bool
{
    $config = app_config();
    $smtp = $config['smtp'] ?? [];

    if (is_array($smtp) && !empty($smtp['enabled'])) {
        return send_smtp_mail($to, $subject, $body, $smtp);
    }

    $from = app_mail_from();
    $headers = [
        'From: ' . mime_header($from['name']) . ' <' . $from['email'] . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    return mail($to, mime_header($subject), $body, implode("\r\n", $headers));
}

function smtp_read_response($socket): string
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }

    return $response;
}

function smtp_expect($socket, array $codes): string
{
    $response = smtp_read_response($socket);
    $code = (int)substr($response, 0, 3);

    if (!in_array($code, $codes, true)) {
        throw new RuntimeException('SMTPエラー: ' . trim($response));
    }

    return $response;
}

function smtp_command($socket, string $command, array $codes): string
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $codes);
}

function sanitize_email_address(string $email): string
{
    return str_replace(["\r", "\n", '<', '>'], '', $email);
}

function send_smtp_mail(string $to, string $subject, string $body, array $smtp): bool
{
    $from = app_mail_from();
    $host = (string)($smtp['host'] ?? '');
    $port = (int)($smtp['port'] ?? 587);
    $secure = (string)($smtp['secure'] ?? 'tls');
    $username = (string)($smtp['username'] ?? '');
    $password = (string)($smtp['password'] ?? '');

    if ($host === '') {
        return false;
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 15);

    try {
        smtp_expect($socket, [220]);
        smtp_command($socket, 'EHLO localhost', [250]);

        if ($secure === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLSに失敗しました。');
            }
            smtp_command($socket, 'EHLO localhost', [250]);
        }

        if ($username !== '' || $password !== '') {
            smtp_command($socket, 'AUTH LOGIN', [334]);
            smtp_command($socket, base64_encode($username), [334]);
            smtp_command($socket, base64_encode($password), [235]);
        }

        $fromEmail = sanitize_email_address($from['email']);
        $toEmail = sanitize_email_address($to);

        smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $headers = [
            'From: ' . mime_header($from['name']) . ' <' . $fromEmail . '>',
            'To: <' . $toEmail . '>',
            'Subject: ' . mime_header($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $body) . "\r\n.";
        smtp_command($socket, $message, [250]);
        smtp_command($socket, 'QUIT', [221]);
        fclose($socket);

        return true;
    } catch (Throwable $e) {
        fclose($socket);
        return false;
    }
}

function send_password_reset_email(string $to, string $username, string $token): bool
{
    $config = app_config();
    $appName = (string)($config['app_name'] ?? '体重・カロリー記録');
    $resetUrl = app_url('reset-password', ['token' => $token]);
    $subject = 'パスワード再設定 | ' . $appName;
    $body = <<<TEXT
{$username} さん

{$appName} のパスワード再設定を受け付けました。
以下のURLを開いて、新しいパスワードを設定してください。

{$resetUrl}

このURLの有効期限は1時間です。
心当たりがない場合は、このメールを破棄してください。
TEXT;

    return send_app_mail($to, $subject, $body);
}

function csrf_token(): string
{
    start_app_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    start_app_session();

    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        echo '不正なリクエストです。';
        exit;
    }
}

function flash_set(string $message): void
{
    start_app_session();
    $_SESSION['flash'] = $message;
}

function flash_get(): ?string
{
    start_app_session();

    if (empty($_SESSION['flash'])) {
        return null;
    }

    $message = (string)$_SESSION['flash'];
    unset($_SESSION['flash']);

    return $message;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}
