<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/error.php';

ensure_schema();
start_app_session();

if (current_user_id()) {
    redirect('index.php');
}

$error = '';
$flash = flash_get();
$config = app_config();
$appName = $config['app_name'] ?? '体重・カロリー記録';
$canRegister = registration_available();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'ユーザー名とパスワードを入力してください。';
    } else {
        try {
            $stmt = db()->prepare("SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = (string)$user['username'];
                redirect('index.php');
            }

            $error = 'ユーザー名またはパスワードが違います。';
        } catch (Throwable $e) {
            report_app_exception($e, 'login');
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
  <title>ログイン - <?= h($appName) ?></title>
  <link rel="stylesheet" href="style.css?v=20260718-public-release-1">
</head>
<body>
  <main class="app narrow">
    <section class="card">
      <p class="eyebrow">Login</p>
      <h1><?= h($appName) ?></h1>
      <p class="description">ログインして記録を保存します。</p>

      <?php if ($flash): ?>
        <p class="alert success"><?= h($flash) ?></p>
      <?php endif; ?>

      <?php if ($error): ?>
        <p class="alert error"><?= h($error) ?></p>
      <?php endif; ?>

      <form method="post" class="form vertical">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

        <div class="field">
          <label for="username">ユーザー名</label>
          <input type="text" id="username" name="username" autocomplete="username" required>
        </div>

        <div class="field">
          <label for="password">パスワード</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="primary-button">ログイン</button>
      </form>

      <p class="under-link"><a href="forgot-password">パスワードを忘れた場合</a></p>

      <?php if ($canRegister): ?>
        <p class="under-link">はじめて使う場合は <a href="signup">アカウント登録</a></p>
      <?php endif; ?>

      <p class="under-link"><a href="./">Body Logとは</a></p>
      <p class="under-link"><a href="privacy">プライバシーポリシー</a>・<a href="terms">利用規約</a></p>
    </section>
  </main>
</body>
</html>
