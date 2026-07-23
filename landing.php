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
  <meta name="description" content="体重と摂取カロリーを記録し、実績から推定TDEEとボディメイクに必要なカロリーを算出する体重管理アプリです。">
  <title>Body Log | 実績から必要なカロリーがわかる</title>
  <link rel="canonical" href="<?= h(app_url('')) ?>">
  <link rel="stylesheet" href="landing.css?v=20260724-redesign-1">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="./" aria-label="<?= h($appName) ?> トップ">
        <span class="brand-mark">B</span>
        <span>Body Log</span>
      </a>
      <nav class="site-nav" aria-label="メインナビゲーション">
        <a href="#mechanism">仕組み</a>
        <a href="#features">できること</a>
        <?php if ($isLoggedIn): ?>
          <a class="nav-cta" href="records">記録画面</a>
        <?php else: ?>
          <a href="login">ログイン</a>
          <a class="nav-cta" href="signup">無料で始める</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-orb hero-orb-one"></div>
      <div class="hero-orb hero-orb-two"></div>
      <div class="container hero-grid">
        <div class="hero-copy">
          <div class="product-label"><span></span> BODYMAKE DATA PLATFORM</div>
          <h1>あなたの実績から、<br><em>必要なカロリー</em>がわかる。</h1>
          <p class="hero-description">
            体重と摂取カロリーを記録するだけ。体重変化からあなた自身の推定TDEEを算出し、
            減量・維持・増量に必要なカロリーをわかりやすくします。
          </p>
          <div class="hero-actions">
            <a class="button button-primary" href="<?= h($primaryHref) ?>">
              <?= h($primaryLabel) ?><span aria-hidden="true">→</span>
            </a>
            <?php if (!$isLoggedIn): ?>
              <a class="button button-text" href="#mechanism">仕組みを見る</a>
            <?php endif; ?>
          </div>
          <div class="hero-trust">
            <span><i>✓</i> 体重だけでも記録可能</span>
            <span><i>✓</i> 無料で利用開始</span>
            <span><i>✓</i> CSV出力対応</span>
          </div>
        </div>

        <div class="dashboard-wrap" aria-label="Body Logのダッシュボードイメージ">
          <div class="dashboard">
            <div class="dashboard-head">
              <div>
                <span class="dashboard-kicker">OVERVIEW</span>
                <strong>ボディメイク状況</strong>
              </div>
              <span class="dashboard-period">直近28日間⌄</span>
            </div>

            <div class="dashboard-main">
              <div class="tdee-card">
                <span class="metric-label">あなたの推定TDEE</span>
                <div class="metric-value">2,650<small>kcal / 日</small></div>
                <p><span class="status-dot"></span>直近の記録から算出</p>
              </div>
              <div class="goal-card">
                <span>減量時の目安</span>
                <strong>2,150 <small>kcal</small></strong>
                <div class="goal-bar"><i></i></div>
                <p>TDEEから −500 kcal</p>
              </div>
            </div>

            <div class="mini-metrics">
              <div><span>7日平均体重</span><strong>72.7 <small>kg</small></strong></div>
              <div><span>前週比</span><strong class="positive">−0.4 <small>kg</small></strong></div>
              <div><span>体重トレンド</span><strong>−0.42 <small>kg / 週</small></strong></div>
            </div>

            <div class="chart-card">
              <div class="chart-head">
                <div><strong>体重トレンド</strong><span>日々の体重と7日平均</span></div>
                <div class="legend"><span class="blue">体重</span><span class="green">7日平均</span></div>
              </div>
              <svg viewBox="0 0 640 190" role="img" aria-label="体重が緩やかに減少しているグラフ">
                <defs>
                  <linearGradient id="areaFill" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#2563eb" stop-opacity=".18"/>
                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0"/>
                  </linearGradient>
                </defs>
                <g class="grid">
                  <line x1="10" y1="35" x2="630" y2="35"/><line x1="10" y1="90" x2="630" y2="90"/>
                  <line x1="10" y1="145" x2="630" y2="145"/>
                </g>
                <path class="area" d="M10 40 C60 28 85 62 130 50 S205 76 250 65 S325 105 370 88 S445 124 490 108 S565 150 630 127 L630 172 L10 172 Z"/>
                <path class="weight-line" d="M10 40 C60 28 85 62 130 50 S205 76 250 65 S325 105 370 88 S445 124 490 108 S565 150 630 127"/>
                <path class="average-line" d="M10 49 C90 50 165 60 245 72 S400 98 480 111 S560 124 630 136"/>
              </svg>
            </div>
          </div>
          <div class="floating-badge"><span>今週の変化</span><strong>順調なペースです</strong></div>
        </div>
      </div>
    </section>

    <section class="value-strip">
      <div class="container value-grid">
        <div><strong>01</strong><span>毎日の数値を<br>シンプルに記録</span></div>
        <div><strong>02</strong><span>実績データから<br>TDEEを推定</span></div>
        <div><strong>03</strong><span>次に摂るべき<br>カロリーを判断</span></div>
      </div>
    </section>

    <section class="mechanism section" id="mechanism">
      <div class="container">
        <div class="section-heading">
          <div>
            <p class="eyebrow">HOW IT WORKS</p>
            <h2>計算式ではなく、<br>あなたの実績を基準に。</h2>
          </div>
          <p>一般的なTDEE計算は年齢や身長、活動レベルから求める目安です。Body Logは、実際の摂取カロリーと体重変化を蓄積し、あなた自身の消費カロリーを逆算します。</p>
        </div>

        <div class="mechanism-panel">
          <article>
            <span class="step-number">01</span>
            <div class="step-icon">＋</div>
            <h3>記録する</h3>
            <p>体重と摂取カロリーを毎日記録。歩数とメモは任意です。</p>
          </article>
          <span class="flow-arrow">→</span>
          <article>
            <span class="step-number">02</span>
            <div class="step-icon">⌁</div>
            <h3>傾向を分析</h3>
            <p>7日平均を使い、一時的な増減と本当の体重変化を分けます。</p>
          </article>
          <span class="flow-arrow">→</span>
          <article class="featured">
            <span class="step-number">03</span>
            <div class="step-icon">◎</div>
            <h3>必要量がわかる</h3>
            <p>推定TDEEと目的に合わせたカロリー目安を確認できます。</p>
          </article>
        </div>
        <div class="formula">推定TDEE <b>＝</b> 平均摂取カロリー <b>−</b> 体重変化によるエネルギー収支</div>
      </div>
    </section>

    <section class="insights section" id="features">
      <div class="container">
        <div class="section-title centered">
          <p class="eyebrow">YOUR INSIGHTS</p>
          <h2>記録が増えるほど、判断が明確になる。</h2>
          <p>ただ数値を残すのではなく、次の行動につながる情報に変換します。</p>
        </div>
        <div class="insight-grid">
          <article class="insight-card insight-large">
            <div>
              <span class="card-label">WEIGHT TREND</span>
              <h3>毎日の増減ではなく、<br>本当の流れを見る。</h3>
              <p>7日平均と前週比で、水分や食事による一時的な変動に振り回されません。</p>
            </div>
            <div class="trend-visual">
              <span>7日平均</span><strong>72.7 kg</strong><em>−0.4 kg</em>
              <svg viewBox="0 0 380 85"><path d="M4 12 C70 18 75 32 135 29 S210 49 250 45 S315 70 376 68"/></svg>
            </div>
          </article>
          <article class="insight-card">
            <span class="card-label">CALORIE TARGET</span>
            <h3>目的別カロリー</h3>
            <p>減量・維持・増量に必要な摂取量を、推定TDEEを基準に判断。</p>
            <div class="target-list"><span>減量 <b>2,150</b></span><span>維持 <b>2,650</b></span><span>増量 <b>2,950</b></span></div>
          </article>
          <article class="insight-card dark-card">
            <span class="card-label">DATA OWNERSHIP</span>
            <h3>記録はあなたのもの。</h3>
            <p>保存したデータはCSVでいつでも出力できます。</p>
            <div class="csv-icon">CSV <span>↓</span></div>
          </article>
        </div>
      </div>
    </section>

    <section class="features section">
      <div class="container feature-layout">
        <div class="section-title">
          <p class="eyebrow">FEATURES</p>
          <h2>続けるために、<br>必要な機能だけ。</h2>
          <p>複雑な設定は不要。毎日の記録から分析まで、ひとつの画面で完結します。</p>
        </div>
        <div class="feature-list">
          <article><span>01</span><div><h3>シンプルな記録</h3><p>日付・体重・摂取カロリー・歩数・メモを保存。</p></div></article>
          <article><span>02</span><div><h3>推定TDEE</h3><p>摂取量と体重変化から実績ベースで算出。</p></div></article>
          <article><span>03</span><div><h3>グラフ分析</h3><p>体重・カロリー・歩数の推移をひと目で確認。</p></div></article>
          <article><span>04</span><div><h3>推定基礎代謝</h3><p>年齢・身長・最新体重から基礎代謝を推定。</p></div></article>
        </div>
      </div>
    </section>

    <section class="final-cta">
      <div class="container cta-inner">
        <div>
          <p class="eyebrow">START TODAY</p>
          <h2>感覚ではなく、<br>自分のデータで変えていく。</h2>
          <p>まずは今日の体重を記録するところから始められます。</p>
        </div>
        <a class="button button-light" href="<?= h($primaryHref) ?>"><?= h($primaryLabel) ?><span>→</span></a>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <a class="brand footer-brand" href="./"><span class="brand-mark">B</span><span>Body Log</span></a>
      <p>あなたの実績から、必要なカロリーを見える化する。</p>
      <nav><a href="privacy">プライバシーポリシー</a><a href="terms">利用規約</a></nav>
    </div>
  </footer>
</body>
</html>
