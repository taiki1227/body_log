<?php
declare(strict_types=1);

/**
 * Cloudflare Turnstile helper.
 * lib.php を読み込んだ後に require してください。
 */
function turnstile_settings(): array
{
    $config = app_config();
    $settings = $config['turnstile'] ?? [];

    return is_array($settings) ? $settings : [];
}

function turnstile_enabled(): bool
{
    $settings = turnstile_settings();
    return !empty($settings['enabled']);
}

function turnstile_site_key(): string
{
    $settings = turnstile_settings();
    return trim((string)($settings['site_key'] ?? ''));
}

function turnstile_should_render(): bool
{
    return turnstile_enabled() && turnstile_site_key() !== '';
}

function turnstile_public_error_message(): string
{
    return 'BOT確認に失敗しました。ページを再読み込みして、もう一度お試しください。';
}

/**
 * Turnstile Siteverify APIへPOSTします。
 */
function turnstile_siteverify_request(array $payload): ?string
{
    $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $body = http_build_query($payload, '', '&');

    if (function_exists('curl_init')) {
        $curl = curl_init($endpoint);
        if ($curl === false) {
            return null;
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!is_string($response) || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        return $response;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($endpoint, false, $context);
    return is_string($response) ? $response : null;
}

/**
 * POSTされたTurnstileトークンをサーバー側で検証します。
 * enabled=false の開発環境では検証をスキップします。
 */
function verify_turnstile(string $expectedAction): bool
{
    if (!turnstile_enabled()) {
        return true;
    }

    $settings = turnstile_settings();
    $siteKey = turnstile_site_key();
    $secretKey = trim((string)($settings['secret_key'] ?? ''));
    $expectedHostname = strtolower(trim((string)($settings['expected_hostname'] ?? '')));

    // enabled=true なのに鍵が不足している場合は、保護を迂回せず失敗させる。
    if ($siteKey === '' || $secretKey === '') {
        error_log('[Body Log][turnstile] Turnstile is enabled, but site_key or secret_key is missing.');
        return false;
    }

    $token = trim((string)($_POST['cf-turnstile-response'] ?? ''));
    if ($token === '') {
        error_log('[Body Log][turnstile] Missing Turnstile response token.');
        return false;
    }

    $responseBody = turnstile_siteverify_request([
        'secret' => $secretKey,
        'response' => $token,
    ]);

    if ($responseBody === null) {
        error_log('[Body Log][turnstile] Siteverify request failed.');
        return false;
    }

    $result = json_decode($responseBody, true);
    if (!is_array($result) || empty($result['success'])) {
        $errorCodes = is_array($result) ? ($result['error-codes'] ?? []) : ['invalid-json'];
        error_log('[Body Log][turnstile] Verification failed: ' . json_encode($errorCodes));
        return false;
    }

    if ($expectedHostname !== '') {
        $actualHostname = strtolower(trim((string)($result['hostname'] ?? '')));
        if ($actualHostname !== $expectedHostname) {
            error_log('[Body Log][turnstile] Hostname mismatch.');
            return false;
        }
    }

    if ($expectedAction !== '') {
        $actualAction = trim((string)($result['action'] ?? ''));
        if (!hash_equals($expectedAction, $actualAction)) {
            error_log('[Body Log][turnstile] Action mismatch.');
            return false;
        }
    }

    return true;
}
