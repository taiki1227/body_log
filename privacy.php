<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

start_app_session();

$config = app_config();
$appName = (string)($config['app_name'] ?? 'Body Log');
$isLoggedIn = current_user_id() !== null;
$operatorName = trim((string)($config['operator_name'] ?? 'Body Log運営者'));
$operatorAddress = trim((string)($config['operator_address'] ?? ''));
$supportEmail = trim((string)($config['support_email'] ?? ''));
$updatedAt = '2026年7月18日';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Body Logにおける個人情報の取扱いについて説明します。">
  <title>プライバシーポリシー | Body Log</title>
  <link rel="canonical" href="<?= h(app_url('privacy')) ?>">
  <link rel="stylesheet" href="landing.css?v=20260718-public-release-1">
  <link rel="stylesheet" href="legal.css?v=20260718-public-release-1">
</head>
<body>
  <header class="site-header">
    <div class="landing-container header-inner">
      <a class="brand" href="./" aria-label="<?= h($appName) ?> トップ">
        <span class="brand-mark">B</span>
        <span>Body Log</span>
      </a>

      <nav class="site-nav" aria-label="メインナビゲーション">
        <a href="terms">利用規約</a>
        <?php if ($isLoggedIn): ?>
          <a class="nav-login" href="records">記録画面</a>
        <?php else: ?>
          <a class="nav-login" href="login">ログイン</a>
          <a class="nav-cta" href="signup">無料で始める</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="legal-main">
    <div class="legal-shell">
      <div class="legal-hero">
        <p class="eyebrow">PRIVACY POLICY</p>
        <h1>プライバシーポリシー</h1>
        <p>Body Logで取得する情報、その利用目的、管理方法および削除方法について説明します。</p>
      </div>

      <article class="legal-card">
        <section class="legal-section">
          <h2>1．適用範囲</h2>
          <p>
            本プライバシーポリシーは、<?= h($operatorName) ?>（以下「運営者」といいます）が提供する
            Webサービス「Body Log」（以下「本サービス」といいます）における利用者情報の取扱いに適用されます。
          </p>
        </section>

        <section class="legal-section">
          <h2>2．取得する情報</h2>
          <p>本サービスでは、利用者が入力した次の情報を取得・保存します。</p>
          <ul>
            <li>アカウント情報：ユーザー名、メールアドレス、パスワードのハッシュ値</li>
            <li>プロフィール情報：性別、年齢、身長</li>
            <li>記録情報：日付、体重、摂取カロリー、歩数、メモ</li>
            <li>技術情報：Cookie、セッション識別子、アクセス日時、IPアドレス、ブラウザ情報、エラーログ等</li>
          </ul>
          <p>
            パスワードはそのまま保存せず、復元できない形式へ変換して保存します。
            メールアドレス、摂取カロリー、歩数およびメモの登録は任意です。
          </p>
        </section>

        <section class="legal-section">
          <h2>3．利用目的</h2>
          <p>取得した情報は、次の目的で利用します。</p>
          <ul>
            <li>本サービスのアカウント登録、本人確認、ログイン認証およびパスワード再設定のため</li>
            <li>体重、摂取カロリー、歩数、メモ等の保存・表示・編集・削除・CSV出力のため</li>
            <li>7日平均、推定基礎代謝、推定TDEE、体重トレンドおよびグラフ等を算出・表示するため</li>
            <li>不正利用の防止、障害調査、セキュリティ確保および本サービスの品質改善のため</li>
            <li>利用者からの問い合わせへの対応および重要な連絡のため</li>
            <li>法令または行政機関・裁判所等の適法な要請に対応するため</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>4．第三者提供および外部委託</h2>
          <p>
            運営者は、本人の同意がある場合、法令に基づく場合、人の生命・身体・財産の保護に必要な場合等を除き、
            個人データを第三者へ提供しません。
          </p>
          <p>
            サーバー運用、データ保存、メール送信等に必要な範囲で、ホスティング事業者やメール配信事業者等へ
            取扱いを委託する場合があります。この場合、委託先を適切に選定し、必要な監督を行います。
          </p>
          <p>本サービスは、利用者情報を広告事業者へ販売しません。</p>
        </section>

        <section class="legal-section">
          <h2>5．Cookieおよびセッション</h2>
          <p>
            本サービスは、ログイン状態の維持、CSRF対策および安全なサービス提供のためにCookieを使用します。
            ブラウザでCookieを無効にすると、ログインを含む本サービスの一部または全部を利用できない場合があります。
          </p>
        </section>

        <section class="legal-section">
          <h2>6．安全管理</h2>
          <p>
            運営者は、アクセス制御、パスワードのハッシュ化、通信の暗号化、CSRF対策、エラー情報の非公開化、
            ログ管理およびバックアップ等、取扱う情報の性質と規模に応じた安全管理措置を講じます。
          </p>
          <p>
            ただし、インターネット上の通信および保存について、完全な安全性を保証するものではありません。
          </p>
        </section>

        <section class="legal-section">
          <h2>7．保存期間と削除方法</h2>
          <ul>
            <li>日々の記録は、記録画面から1件ずつ削除できます。</li>
            <li>保存した記録は、記録画面の「全削除」からまとめて削除できます。</li>
            <li>アカウントおよび関連情報の削除を希望する場合は、下記問い合わせ窓口へ連絡してください。</li>
          </ul>
          <p>
            本人確認後、法令上または運用上保存が必要な情報を除き、合理的な期間内に削除します。
            バックアップやセキュリティログには、削除後も一定期間情報が残る場合があります。
          </p>
        </section>

        <section class="legal-section">
          <h2>8．開示・訂正・利用停止等</h2>
          <p>
            利用者は、自己に関する保有個人データについて、利用目的の通知、開示、訂正、追加、削除、
            利用停止または第三者提供の停止等を求めることができます。
          </p>
          <p>
            希望する手続と対象情報を問い合わせ窓口へ連絡してください。
            運営者は、なりすまし防止のため本人確認を行ったうえで、法令に従い対応します。
          </p>
        </section>

        <section class="legal-section">
          <h2>9．18歳未満の利用者</h2>
          <p>
            18歳未満の方は、必要に応じて保護者等の法定代理人の同意を得たうえで本サービスを利用してください。
          </p>
        </section>

        <section class="legal-section">
          <h2>10．本ポリシーの変更</h2>
          <p>
            法令改正、機能追加または運用変更等に応じて、本ポリシーを変更することがあります。
            重要な変更を行う場合は、本サービス上で分かりやすく告知します。
          </p>
        </section>

        <section class="legal-section">
          <h2>11．運営者・問い合わせ窓口</h2>
          <p>運営者：<?= h($operatorName) ?></p>
          <p>
            所在地：
            <?= $operatorAddress !== ''
                ? h($operatorAddress)
                : '本人からの求めに応じて、遅滞なく回答します。' ?>
          </p>
          <p>
            メール：
            <?php if ($supportEmail !== ''): ?>
              <a href="mailto:<?= h($supportEmail) ?>"><?= h($supportEmail) ?></a>
            <?php else: ?>
              config.phpのsupport_emailを設定してください。
            <?php endif; ?>
          </p>

          <?php if ($supportEmail === ''): ?>
            <p class="legal-note">
              一般公開前に、config.phpへ問い合わせ用メールアドレスを設定してください。
            </p>
          <?php endif; ?>
        </section>

        <p class="legal-date">制定・最終更新：<?= h($updatedAt) ?></p>
      </article>

      <div class="legal-back">
        <a href="./">Body Logトップへ</a>
        <a href="terms">利用規約を見る</a>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="landing-container footer-inner">
      <span>Body Log</span>
      <p><a href="terms">利用規約</a></p>
    </div>
  </footer>
</body>
</html>
