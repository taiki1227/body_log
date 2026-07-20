<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/error.php';
require __DIR__ . '/app/password_reset.php';

ensure_schema();
start_app_session();

$config = app_config();
$appName = $config['app_name'] ?? '体重・カロリー記録';
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$user = $token !== '' ? find_user_by_reset_token($token) : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $user = $token !== '' ? find_user_by_reset_token($token) : null;
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    if (!$user) {
        $error = '再設定URLが無効、または有効期限が切れています。';
    } elseif (mb_strlen($password) < 8) {
        $error = 'パスワードは8文字以上にしてください。';
    } elseif ($password !== $passwordConfirm) {
        $error = '確認用パスワードが一致しません。';
    } else {
        try {
            reset_user_password((int)$user['id'], $password);
            flash_set('パスワードを再設定しました。新しいパスワードでログインしてください。');
            redirect('login.php');
        } catch (Throwable $e) {
            report_app_exception($e, 'reset_password');
            $error = public_error_message();
        }
    }
}

$tokenIsValid = (bool)$user;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>新しいパスワード設定 - <?= h($appName) ?></title>
  <link rel="stylesheet" href="style.css?v=20260718-public-release-1">
</head>
<body>
  <main class="app narrow">
    <section class="card">
      <p class="eyebrow">Password Reset</p>
      <h1>新しいパスワード設定</h1>

      <?php if ($error): ?>
        <p class="alert error"><?= h($error) ?></p>
      <?php endif; ?>

      <?php if (!$tokenIsValid): ?>
        <p class="alert error">再設定URLが無効、または有効期限が切れています。</p>
        <p class="description">もう一度、パスワード再設定メールを送信してください。</p>
        <p class="under-link"><a href="forgot-password">再設定メールを送信する</a></p>
      <?php else: ?>
        <p class="description"><?= h((string)$user['username']) ?> さんの新しいパスワードを設定します。</p>

        <form method="post" class="form vertical">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="token" value="<?= h($token) ?>">

          <div class="field">
            <label for="password">新しいパスワード</label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
          </div>

          <div class="field">
            <label for="password_confirm">新しいパスワード確認</label>
            <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
          </div>

          <button type="submit" class="primary-button">パスワードを変更</button>
        </form>
      <?php endif; ?>

      <p class="under-link"><a href="login">ログイン画面へ戻る</a></p>
      <p class="under-link"><a href="./">Body Logとは</a></p>
    </section>
  </main>
</body>
</html>
