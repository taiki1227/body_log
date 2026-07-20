<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

start_app_session();

$config = app_config();
$appName = (string)($config['app_name'] ?? 'Body Log');
$isLoggedIn = current_user_id() !== null;
$operatorName = trim((string)($config['operator_name'] ?? 'Body Log運営者'));
$supportEmail = trim((string)($config['support_email'] ?? ''));
$updatedAt = '2026年7月18日';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Body Logの利用条件について説明します。">
  <title>利用規約 | Body Log</title>
  <link rel="canonical" href="<?= h(app_url('terms')) ?>">
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
        <a href="privacy">プライバシーポリシー</a>
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
        <p class="eyebrow">TERMS OF SERVICE</p>
        <h1>利用規約</h1>
        <p>Body Logを利用する際の条件と注意事項を定めています。</p>
      </div>

      <article class="legal-card">
        <section class="legal-section">
          <h2>第1条（適用）</h2>
          <p>
            本規約は、<?= h($operatorName) ?>（以下「運営者」といいます）が提供する
            Webサービス「Body Log」（以下「本サービス」といいます）の利用条件を定めるものです。
          </p>
          <p>
            利用者は、本規約およびプライバシーポリシーに同意したうえで本サービスを利用します。
          </p>
        </section>

        <section class="legal-section">
          <h2>第2条（サービス内容）</h2>
          <p>
            本サービスは、体重、摂取カロリー、歩数、メモ等を記録し、平均値、グラフ、
            推定基礎代謝、推定TDEEおよび体重トレンド等を表示する体重管理支援サービスです。
          </p>
          <p>
            本サービスは開発・改善中のベータ版を含み、機能、表示方法、算出方法または提供条件が変更される場合があります。
          </p>
        </section>

        <section class="legal-section">
          <h2>第3条（アカウント登録）</h2>
          <ol>
            <li>利用者は、正確かつ最新の情報を登録してください。</li>
            <li>利用者は、ユーザー名およびパスワードを自己の責任で適切に管理してください。</li>
            <li>アカウントを第三者へ譲渡、貸与または共有してはなりません。</li>
            <li>登録情報に変更が生じた場合は、アカウント設定から速やかに更新してください。</li>
          </ol>
        </section>

        <section class="legal-section">
          <h2>第4条（禁止事項）</h2>
          <p>利用者は、次の行為をしてはなりません。</p>
          <ul>
            <li>法令または公序良俗に違反する行為</li>
            <li>他人になりすまして本サービスを利用する行為</li>
            <li>他人のアカウント、パスワードまたは個人情報を不正に取得・利用する行為</li>
            <li>本サービスへ過度な負荷をかける行為、脆弱性を探索する行為または不正アクセス</li>
            <li>プログラム、データまたはネットワークの破壊・妨害を目的とする行為</li>
            <li>本サービスまたは第三者の権利・利益を侵害する行為</li>
            <li>運営者が不適切と合理的に判断するその他の行為</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>第5条（健康・栄養情報に関する注意）</h2>
          <p>
            本サービスが表示する推定基礎代謝、推定TDEE、体重トレンド、目標カロリーその他の数値は、
            利用者が入力したデータおよび一般的な計算式に基づく参考値です。
          </p>
          <p>
            本サービスは医療行為、診断、治療、栄養指導または専門家による助言を提供するものではなく、
            数値の正確性、完全性、特定目的への適合性を保証しません。
            体調に不安がある場合や治療中の場合は、医師または管理栄養士等の専門家へ相談してください。
          </p>
        </section>

        <section class="legal-section">
          <h2>第6条（データの管理とバックアップ）</h2>
          <ol>
            <li>利用者は、入力内容に誤りがないか自ら確認してください。</li>
            <li>重要な記録は、CSV出力等を利用して利用者自身でも保管してください。</li>
            <li>障害、保守、更新、不正アクセスその他の事情により、データが消失・破損する可能性があります。</li>
            <li>運営者は合理的なバックアップに努めますが、全データの完全な復元を保証しません。</li>
          </ol>
        </section>

        <section class="legal-section">
          <h2>第7条（サービスの変更・停止）</h2>
          <p>
            運営者は、保守、障害対応、セキュリティ確保、法令対応、サービス改善その他必要な場合に、
            本サービスの全部または一部を変更、中断または終了できます。
          </p>
          <p>
            重大な影響がある場合は、緊急時を除き、本サービス上で可能な範囲の事前告知を行います。
          </p>
        </section>

        <section class="legal-section">
          <h2>第8条（利用停止・アカウント削除）</h2>
          <p>
            運営者は、利用者が本規約に違反した場合、不正利用やセキュリティ上の危険がある場合等に、
            アカウントまたは本サービスの利用を停止できるものとします。
          </p>
          <p>
            利用者がアカウント削除を希望する場合は、プライバシーポリシー記載の問い合わせ窓口へ連絡してください。
          </p>
        </section>

        <section class="legal-section">
          <h2>第9条（知的財産権）</h2>
          <p>
            本サービスのプログラム、画面、文章、ロゴ、デザインその他のコンテンツに関する権利は、
            運営者または正当な権利者に帰属します。
          </p>
          <p>
            利用者が入力した体重、カロリー、歩数、メモ等のデータに関する権利は利用者に留保されます。
            運営者は、本サービスの提供・保守に必要な範囲でのみ当該データを取り扱います。
          </p>
        </section>

        <section class="legal-section">
          <h2>第10条（免責および責任の範囲）</h2>
          <p>
            運営者は、本サービスを現状有姿で提供し、停止しないこと、エラーがないこと、
            または表示結果が利用者の期待に合致することを保証しません。
          </p>
          <p>
            運営者の故意または重大な過失による場合を除き、運営者が負う損害賠償責任は、
            適用法令上許される範囲で、利用者に現実に発生した通常かつ直接の損害に限られます。
          </p>
          <p>
            消費者契約法その他の強行法規により本条の一部が無効となる場合、当該法令が優先して適用されます。
          </p>
        </section>

        <section class="legal-section">
          <h2>第11条（規約の変更）</h2>
          <p>
            運営者は、法令改正、機能追加または運用変更等に応じて、本規約を変更することがあります。
            利用者への重大な影響がある変更は、本サービス上で分かりやすく告知します。
          </p>
        </section>

        <section class="legal-section">
          <h2>第12条（準拠法・管轄）</h2>
          <p>本規約は日本法に準拠します。</p>
          <p>
            本サービスに関して紛争が生じた場合は、当事者間で誠実に協議し、
            解決しない場合は、法令により認められる範囲で東京地方裁判所を第一審の合意管轄裁判所とします。
          </p>
        </section>

        <section class="legal-section">
          <h2>第13条（問い合わせ）</h2>
          <p>
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
        <a href="privacy">プライバシーポリシーを見る</a>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="landing-container footer-inner">
      <span>Body Log</span>
      <p><a href="privacy">プライバシーポリシー</a></p>
    </div>
  </footer>
</body>
</html>
