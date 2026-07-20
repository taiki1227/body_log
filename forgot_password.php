<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/error.php';
require __DIR__ . '/app/password_reset.php';

ensure_schema();
start_app_session();

if (current_user_id()) {
    redirect('index.php');
}

$config = app_config();
$appName = $config['app_name'] ?? '体重・カロリー記録';
$error = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = normalize_email((string)($_POST['email'] ?? ''));

    if (!is_valid_email($email)) {
        $error = 'メールアドレスを正しく入力してください。';
    } else {
        try {
            $user = find_user_by_email($email);

            if ($user) {
                $token = create_password_reset_token((int)$user['id']);
                $mailOk = send_password_reset_email($email, (string)$user['username'], $token);

                if (!$mailOk) {
                    report_app_error('Password reset email could not be sent.', 'forgot_password.mail');
                    $error = public_error_message();
                } else {
                    $sent = true;
                }
            } else {
                // 登録有無を外から判別しにくくするため、未登録でも完了表示にする
                $sent = true;
            }
        } catch (Throwable $e) {
            report_app_exception($e, 'forgot_password');
            $error = public_error_message();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>パスワード再設定 - <?= h($appName) ?></title>
  <link rel="stylesheet" href="style.css?v=20260718-public-release-1">
</head>
<body>
  <main class="app narrow">
    <section class="card">
      <p class="eyebrow">Password Reset</p>
      <h1>パスワード再設定</h1>
      <p class="description">アカウント設定で登録したメールアドレスに、再設定用URLを送信します。</p>

      <?php if ($sent): ?>
        <p class="alert success">登録済みメールアドレス宛に、再設定リンクを送信しました。メールが届かない場合は迷惑メールフォルダをご確認ください。</p>
      <?php endif; ?>

      <?php if ($error): ?>
        <p class="alert error"><?= h($error) ?></p>
      <?php endif; ?>

      <?php if (!$sent): ?>
        <form method="post" class="form vertical">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

          <div class="field">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" inputmode="email" autocomplete="email" placeholder="name@example.com" required>
          </div>

          <button type="submit" class="primary-button">再設定メールを送信</button>
        </form>
      <?php endif; ?>

      <p class="under-link"><a href="login">ログイン画面へ戻る</a></p>
      <p class="under-link"><a href="./">Body Logとは</a></p>
    </section>
  </main>
</body>
</html>
