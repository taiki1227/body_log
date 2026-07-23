<?php
declare(strict_types=1);

/**
 * 例外の詳細をサーバーのPHPエラーログへ記録します。
 *
 * POST内容やメールアドレスなど、利用者が入力した値はログへ出しません。
 * REQUEST_URIもクエリ文字列を除いたパスだけを記録します。
 */
function report_app_exception(Throwable $e, string $context = 'unknown'): void
{
    $requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
    $requestMethod = (string)($_SERVER['REQUEST_METHOD'] ?? '');
    $message = sprintf(
        '[Body Log][%s] %s %s | %s: %s in %s:%d',
        $context,
        $requestMethod,
        $requestPath,
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );

    error_log($message . PHP_EOL . $e->getTraceAsString());
}

/**
 * 例外ではない運用上のエラーをサーバーログへ記録します。
 */
function report_app_error(string $message, string $context = 'unknown'): void
{
    $requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
    $requestMethod = (string)($_SERVER['REQUEST_METHOD'] ?? '');

    error_log(sprintf(
        '[Body Log][%s] %s %s | %s',
        $context,
        $requestMethod,
        $requestPath,
        $message
    ));
}

/**
 * 利用者へ表示する共通エラーメッセージです。
 */
function public_error_message(): string
{
    return 'エラーが発生しました。時間をおいてもう一度お試しください。';
}
