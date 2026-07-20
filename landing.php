<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

start_app_session();

$config = app_config();
$appName = (string)($config['app_name'] ?? 'Body Log');
$isLoggedIn = current_user_id() !== null;
$primaryHref = $isLoggedIn ? 'records' : 'signup';
$primaryLabel = $isLoggedIn ? '記録画面を開く' : '無料で始める';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta
    name="description"
    content="体重・摂取カロリー・歩数を記録し、7日平均や実績ベースの推定TDEEから減量の進み方を確認できる体重管理アプリです。"
  >
  <title>Body Log | 体重と食事から減量の進み方を見える化</title>
  <link rel="canonical" href="<?= h(app_url('')) ?>">
  <link rel="stylesheet" href="landing.css?v=20260718-public-release-1">
</head>
<body>
  <header class="site-header">
    <div class="landing-container header-inner">
      <a class="brand" href="./" aria-label="<?= h($appName) ?> トップ">
        <span class="brand-mark">B</span>
        <span>Body Log</span>
      </a>

      <nav class="site-nav" aria-label="メインナビゲーション">
        <a href="#features">できること</a>
        <a href="#how-to-use">使い方</a>

        <?php if ($isLoggedIn): ?>
          <a class="nav-login" href="records">記録画面</a>
        <?php else: ?>
          <a class="nav-login" href="login">ログイン</a>
          <a class="nav-cta" href="signup">無料で始める</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="landing-container hero-grid">
        <div class="hero-copy">
          <p class="eyebrow">BODY &amp; CALORIE TRACKING</p>
          <h1>
            体重と食事を記録して、<br>
            <span>減量の進み方を見える化。</span>
          </h1>
          <p class="hero-description">
            毎日の体重・摂取カロリー・歩数をシンプルに記録。
            日々の増減に振り回されず、7日平均と実績から算出した推定TDEEで、
            今の食事量が自分に合っているか確認できます。
          </p>

          <div class="hero-actions">
            <a class="button button-primary" href="<?= h($primaryHref) ?>"><?= h($primaryLabel) ?></a>
            <?php if (!$isLoggedIn): ?>
              <a class="button button-secondary" href="login">ログイン</a>
            <?php endif; ?>
          </div>

          <p class="hero-note">体重だけでも記録可能。摂取カロリー・歩数・メモは任意です。</p>
        </div>

        <div class="app-preview" aria-label="アプリ画面のイメージ">
          <div class="preview-window">
            <div class="preview-topbar">
              <div>
                <span class="preview-eyebrow">Daily Log</span>
                <strong>体重・カロリー記録</strong>
              </div>
              <span class="preview-user">demo_user</span>
            </div>

            <div class="preview-input">
              <span>今日の記録</span>
              <div class="preview-input-grid">
                <div><small>体重</small><strong>72.5 kg</strong></div>
                <div><small>摂取カロリー</small><strong>2,450 kcal</strong></div>
                <div><small>歩数</small><strong>8,230 歩</strong></div>
              </div>
            </div>

            <div class="preview-cards">
              <div class="preview-card">
                <small>7日平均体重</small>
                <strong>72.7 kg</strong>
              </div>
              <div class="preview-card">
                <small>前週比</small>
                <strong class="preview-negative">−0.4 kg</strong>
              </div>
              <div class="preview-card preview-card-accent">
                <small>推定TDEE</small>
                <strong>2,650 kcal</strong>
                <span>直近の実績から算出</span>
              </div>
              <div class="preview-card">
                <small>体重トレンド</small>
                <strong>−0.42 kg/週</strong>
              </div>
            </div>

            <div class="preview-chart">
              <div class="chart-head">
                <span>体重推移</span>
                <small>7日平均</small>
              </div>
              <svg viewBox="0 0 560 180" role="img" aria-label="右肩下がりの体重推移グラフ">
                <defs>
                  <linearGradient id="chartFill" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#2563eb" stop-opacity=".22"/>
                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0"/>
                  </linearGradient>
                </defs>
                <line x1="24" y1="35" x2="536" y2="35" class="grid-line"/>
                <line x1="24" y1="90" x2="536" y2="90" class="grid-line"/>
                <line x1="24" y1="145" x2="536" y2="145" class="grid-line"/>
                <path
                  d="M24 48 C70 40, 95 68, 135 62 S205 82, 245 77 S310 105, 350 94 S420 122, 460 112 S505 138, 536 126 L536 160 L24 160 Z"
                  fill="url(#chartFill)"
                />
                <path
                  d="M24 48 C70 40, 95 68, 135 62 S205 82, 245 77 S310 105, 350 94 S420 122, 460 112 S505 138, 536 126"
                  class="chart-line"
                />
                <path
                  d="M24 57 C95 59, 165 68, 235 80 S375 105, 445 116 S505 124, 536 130"
                  class="average-line"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="problem-section">
      <div class="landing-container">
        <div class="section-heading">
          <p class="eyebrow">WHY BODY LOG</p>
          <h2>こんな悩みを、記録データから整理します。</h2>
        </div>

        <div class="problem-grid">
          <article>
            <span>01</span>
            <h3>何kcal食べれば痩せるのか分からない</h3>
            <p>一般的な計算だけではなく、自分の体重変化と食事記録から実際の消費量を推定します。</p>
          </article>
          <article>
            <span>02</span>
            <h3>毎日の体重変動に振り回される</h3>
            <p>日々の数値だけでなく7日平均を表示し、短期的な水分変動と中期的な流れを分けて確認できます。</p>
          </article>
          <article>
            <span>03</span>
            <h3>減量が順調なのか判断しづらい</h3>
            <p>前週比と体重トレンドを表示し、今の摂取カロリーでどの程度のペースで進んでいるか見える化します。</p>
          </article>
        </div>
      </div>
    </section>

    <section class="features-section" id="features">
      <div class="landing-container">
        <div class="section-heading centered">
          <p class="eyebrow">FEATURES</p>
          <h2>記録するだけで、必要な数字が見えてくる。</h2>
          <p>複雑な設定を増やさず、減量や体重管理に必要な情報をひとつの画面にまとめます。</p>
        </div>

        <div class="feature-grid">
          <article class="feature-card">
            <div class="feature-icon">01</div>
            <h3>毎日の記録をシンプルに管理</h3>
            <p>日付・体重・摂取カロリー・歩数・メモを記録。体重だけでも保存できるため、無理なく続けられます。</p>
          </article>

          <article class="feature-card">
            <div class="feature-icon">02</div>
            <h3>7日平均で本当の変化を見る</h3>
            <p>水分や外食による一時的な増減ではなく、平均値から体重の流れを確認できます。</p>
          </article>

          <article class="feature-card feature-card-main">
            <div class="feature-icon">03</div>
            <h3>実績から推定TDEEを算出</h3>
            <p>期間中の平均摂取カロリーと体重トレンドから、1日の消費カロリーを逆算します。</p>
            <div class="formula">
              推定TDEE ＝ 平均摂取カロリー − 体重変化によるエネルギー収支
            </div>
          </article>

          <article class="feature-card">
            <div class="feature-icon">04</div>
            <h3>グラフで変化を確認</h3>
            <p>体重・摂取カロリー・歩数の推移をグラフ表示。数字の一覧だけでは分かりにくい変化を把握できます。</p>
          </article>

          <article class="feature-card">
            <div class="feature-icon">05</div>
            <h3>推定基礎代謝を表示</h3>
            <p>性別・年齢・身長・最新体重から基礎代謝を推定し、日々の消費エネルギーを考える参考にできます。</p>
          </article>

          <article class="feature-card">
            <div class="feature-icon">06</div>
            <h3>データを書き出せる</h3>
            <p>保存した記録はCSV形式で出力可能。手元での保管や、表計算ソフトでの分析にも利用できます。</p>
          </article>
        </div>
      </div>
    </section>

    <section class="how-section" id="how-to-use">
      <div class="landing-container">
        <div class="section-heading centered">
          <p class="eyebrow">HOW TO USE</p>
          <h2>使い方は3ステップ。</h2>
        </div>

        <div class="steps">
          <article>
            <span>STEP 1</span>
            <h3>アカウントを作成</h3>
            <p>ユーザー名とパスワードを設定して登録します。</p>
          </article>
          <div class="step-line" aria-hidden="true"></div>
          <article>
            <span>STEP 2</span>
            <h3>体重と食事を記録</h3>
            <p>体重を中心に、摂取カロリーや歩数を毎日入力します。</p>
          </article>
          <div class="step-line" aria-hidden="true"></div>
          <article>
            <span>STEP 3</span>
            <h3>平均とトレンドを確認</h3>
            <p>7日平均や推定TDEEから、今の減量ペースを確認します。</p>
          </article>
        </div>
      </div>
    </section>

    <section class="final-cta">
      <div class="landing-container final-cta-inner">
        <div>
          <p class="eyebrow">START TODAY</p>
          <h2>毎日の記録から、<br>自分に合った減量ペースを見つけよう。</h2>
          <p>まずは体重を記録するところから始められます。</p>
        </div>
        <a class="button button-light" href="<?= h($primaryHref) ?>"><?= h($primaryLabel) ?></a>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="landing-container footer-inner">
      <span>Body Log</span>
      <p>
        体重・食事・活動量を記録し、変化を見える化するWebアプリ。
        <a href="privacy">プライバシーポリシー</a>
        <a href="terms">利用規約</a>
      </p>
    </div>
  </footer>
</body>
</html>
