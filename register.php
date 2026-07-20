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
$canRegister = registration_available();
$count = user_count();
$needsCode = $count > 0 && !empty($config['registration_code']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$canRegister) {
        $error = '新規登録は停止中です。';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $emailRaw = trim((string)($_POST['email'] ?? ''));
        $email = $emailRaw === '' ? '' : normalize_email($emailRaw);
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
        $registrationCode = trim((string)($_POST['registration_code'] ?? ''));
        $termsAccepted = (string)($_POST['terms_accepted'] ?? '') === '1';

        if ($needsCode && !hash_equals((string)$config['registration_code'], $registrationCode)) {
            $error = '登録コードが違います。';
        } elseif ($username === '' || mb_strlen($username) > 100) {
            $error = 'ユーザー名を入力してください。';
        } elseif (!preg_match('/^[a-zA-Z0-9_\-\.]{3,100}$/', $username)) {
            $error = 'ユーザー名は3文字以上で、半角英数字・ハイフン・アンダーバー・ドットのみ使えます。';
        } elseif ($email !== '' && !is_valid_email($email)) {
            $error = 'メールアドレスを正しく入力してください。';
        } elseif ($email !== '' && find_user_by_email($email)) {
            $error = 'このメールアドレスはすでに使われています。';
        } elseif (mb_strlen($password) < 8) {
            $error = 'パスワードは8文字以上にしてください。';
        } elseif ($password !== $passwordConfirm) {
            $error = '確認用パスワードが一致しません。';
        } elseif (!$termsAccepted) {
            $error = '利用規約とプライバシーポリシーへの同意が必要です。';
        } else {
            try {
                $stmt = db()->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email === '' ? null : $email,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)db()->lastInsertId();
                $_SESSION['username'] = $username;

                flash_set('アカウントを作成しました。');
                redirect('index.php');
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? null) === 1062) {
                    $error = 'このユーザー名はすでに使われています。';
                } else {
                    report_app_exception($e, 'register.database');
                    $error = public_error_message();
                }
            } catch (Throwable $e) {
                report_app_exception($e, 'register.unexpected');
                $error = public_error_message();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>アカウント登録 - <?= h($appName) ?></title>
  <link rel="stylesheet" href="style.css?v=20260718-public-release-1">
  <style>
    .consent-check {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      color: var(--muted);
      font-size: 13px;
      font-weight: 650;
      line-height: 1.65;
      cursor: pointer;
    }

    .consent-check input {
      flex: 0 0 auto;
      width: 18px;
      height: 18px;
      margin: 3px 0 0;
    }

    .consent-check a {
      color: var(--primary);
      font-weight: 800;
      text-decoration: underline;
      text-underline-offset: 2px;
    }

    .legal-under-links {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 8px 16px;
      margin-top: 18px;
      font-size: 13px;
    }
  </style>
</head>
<body>
  <main class="app narrow">
    <section class="card">
      <p class="eyebrow">Register</p>
      <h1>アカウント登録</h1>
      <p class="description">ユーザー名とパスワードを作成します。メールアドレスを登録しておくと、パスワード再設定に使えます。</p>

      <?php if ($error): ?>
        <p class="alert error"><?= h($error) ?></p>
      <?php endif; ?>

      <?php if (!$canRegister): ?>
        <p class="alert error">現在、新規登録は停止中です。</p>
        <p class="description"><a href="login.php">ログイン画面へ戻る</a></p>
      <?php else: ?>
        <?php if ($count === 0): ?>
          <p class="alert success">最初のユーザーを作成します。</p>
        <?php endif; ?>

        <form method="post" class="form vertical">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

          <?php if ($needsCode): ?>
            <div class="field">
              <label for="registration_code">登録コード</label>
              <input type="password" id="registration_code" name="registration_code" required>
            </div>
          <?php endif; ?>

          <div class="field">
            <label for="username">ユーザー名</label>
            <input type="text" id="username" name="username" autocomplete="username" placeholder="taiki" required>
          </div>

          <div class="field">
            <label for="email">メールアドレス <span>任意</span></label>
            <input type="email" id="email" name="email" inputmode="email" autocomplete="email" placeholder="name@example.com">
          </div>

          <div class="field">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
          </div>

          <div class="field">
            <label for="password_confirm">パスワード確認</label>
            <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
          </div>

          <label class="consent-check">
            <input type="checkbox" name="terms_accepted" value="1" required>
            <span>
              <a href="terms" target="_blank" rel="noopener">利用規約</a>および
              <a href="privacy" target="_blank" rel="noopener">プライバシーポリシー</a>
              を確認し、同意します。
            </span>
          </label>

          <button type="submit" class="primary-button">同意して登録する</button>
        </form>

        <p class="under-link"><a href="login.php">ログイン画面へ戻る</a></p>
      <?php endif; ?>

      <div class="legal-under-links">
        <a href="./">Body Logとは</a>
        <a href="privacy">プライバシーポリシー</a>
        <a href="terms">利用規約</a>
      </div>
    </section>
  </main>
</body>
</html>
