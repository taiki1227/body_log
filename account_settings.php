<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/error.php';

$userId = require_login();
$config = app_config();
$appName = $config['app_name'] ?? '体重・カロリー記録';
$username = '';

function parse_account_settings_input(int $userId): array
{
    $username = trim((string)($_POST['username'] ?? ''));
    $emailRaw = trim((string)($_POST['email'] ?? ''));
    $email = $emailRaw === '' ? '' : normalize_email($emailRaw);
    $sex = (string)($_POST['profile_sex'] ?? '');
    $ageRaw = trim((string)($_POST['profile_age'] ?? ''));
    $heightRaw = trim((string)($_POST['profile_height_cm'] ?? ''));

    $usernameOk = preg_match('/^[a-zA-Z0-9_\-\.]{3,100}$/', $username) === 1;
    $usernameDuplicate = $usernameOk && username_is_used_by_other_user($userId, $username);
    $emailOk = $email === '' || is_valid_email($email);
    $emailDuplicate = $email !== '' && email_is_used_by_other_user($userId, $email);
    $sexOk = in_array($sex, ['male', 'female'], true);
    $ageOk = ctype_digit($ageRaw) && (int)$ageRaw >= 1 && (int)$ageRaw <= 120;
    $heightOk = is_numeric($heightRaw) && (float)$heightRaw >= 100 && (float)$heightRaw <= 250;

    return [
        'username' => $username,
        'email' => $email,
        'sex' => $sex,
        'age' => $ageOk ? (int)$ageRaw : 0,
        'height_cm' => $heightOk ? round((float)$heightRaw, 1) : 0.0,
        'username_duplicate' => $usernameDuplicate,
        'email_duplicate' => $emailDuplicate,
        'valid' => $usernameOk && !$usernameDuplicate && $emailOk && !$emailDuplicate && $sexOk && $ageOk && $heightOk,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $input = parse_account_settings_input($userId);

        if ($input['username_duplicate']) {
            flash_set('このユーザー名はすでに使われています。');
            redirect('account_settings.php');
        }

        if ($input['email_duplicate']) {
            flash_set('このメールアドレスは別のユーザーで使用されています。');
            redirect('account_settings.php');
        }

        if (!$input['valid']) {
            flash_set('ユーザー名・メールアドレス・性別・年齢・身長を正しく入力してください。');
            redirect('account_settings.php');
        }

        save_user_account_settings(
            $userId,
            $input['username'],
            $input['email'] === '' ? null : $input['email'],
            $input['sex'],
            $input['age'],
            $input['height_cm']
        );

        $_SESSION['username'] = $input['username'];

        flash_set('アカウント設定を保存しました。');
        redirect('account_settings.php');
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) === 1062) {
            flash_set('このユーザー名、またはメールアドレスはすでに使われています。');
        } else {
            report_app_exception($e, 'account.database');
            flash_set(public_error_message());
        }
        redirect('account_settings.php');
    } catch (Throwable $e) {
        report_app_exception($e, 'account.unexpected');
        flash_set(public_error_message());
        redirect('account_settings.php');
    }
}

$userAccount = get_user_account($userId);
$profileIsComplete = (bool)$userAccount['is_profile_complete'];
$username = (string)($userAccount['username'] ?? ($_SESSION['username'] ?? ''));
$email = (string)($userAccount['email'] ?? '');
$profileSex = (string)$userAccount['sex'];
$profileAge = $userAccount['age'] !== null ? (string)$userAccount['age'] : '';
$profileHeightCm = $userAccount['height_cm'] !== null ? number_format((float)$userAccount['height_cm'], 1, '.', '') : '';
$flash = flash_get();
$pageTitle = 'アカウント設定';
$pageEyebrow = 'Body Log';
$pageDescription = 'ユーザー名、メールアドレス、推定基礎代謝の計算に使う情報を設定します。';
$pageActiveNav = 'settings';
$pageAppClass = 'settings-app';

require __DIR__ . '/app/partials/app_header.php';
?>

<?php if ($flash): ?>
      <p class="alert success"><?= h($flash) ?></p>
    <?php endif; ?>

    <section class="card settings-card">
      <div class="section-title">
        <div>
          <h2>ログイン・プロフィール</h2>
          <p class="list-meta">プロフィール：<?= $profileIsComplete ? '設定済み' : '未設定' ?> ／ メール：<?= $email !== '' ? '設定済み' : '未設定' ?></p>
        </div>
      </div>

      <form method="post" class="settings-form account-settings-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

        <div class="field username-field">
          <label for="username">ユーザー名</label>
          <input type="text" id="username" name="username" autocomplete="username" value="<?= h($username) ?>" required>
        </div>

        <div class="field email-field">
          <label for="email">メールアドレス <span>任意</span></label>
          <input type="email" id="email" name="email" inputmode="email" autocomplete="email" placeholder="name@example.com" value="<?= h($email) ?>">
        </div>

        <div class="field">
          <label for="profile_sex">性別</label>
          <select id="profile_sex" name="profile_sex" required>
            <option value="" <?= $profileSex === '' ? 'selected' : '' ?>>選択</option>
            <option value="male" <?= $profileSex === 'male' ? 'selected' : '' ?>>男性</option>
            <option value="female" <?= $profileSex === 'female' ? 'selected' : '' ?>>女性</option>
          </select>
        </div>

        <div class="field compact-field">
          <label for="profile_age">年齢</label>
          <input type="number" id="profile_age" name="profile_age" inputmode="numeric" step="1" min="1" max="120" value="<?= h($profileAge) ?>" required>
        </div>

        <div class="field compact-field">
          <label for="profile_height_cm">身長 <span>cm</span></label>
          <input type="number" id="profile_height_cm" name="profile_height_cm" inputmode="decimal" step="0.1" min="100" max="250" value="<?= h($profileHeightCm) ?>" required>
        </div>

        <div class="form-actions">
          <button type="submit" class="primary-button">設定を保存</button>
        </div>
      </form>

      <p class="profile-note">ユーザー名は画面右上の表示とログイン時のユーザー名に使います。メールアドレスはパスワード再設定メールの送信に使います。性別・年齢・身長は、記録画面の「推定基礎代謝」の計算に使います。</p>
    </section>

<?php require __DIR__ . '/app/partials/app_footer.php'; ?>
