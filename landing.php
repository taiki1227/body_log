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
  <meta name="description" content="体重と摂取カロリーを記録し、実際の体重変化から推定TDEEを算出。自分に必要な摂取カロリーを判断できるボディメイク記録アプリです。">
  <meta name="theme-color" content="#0b1411">
  <title>Body Log | 実績から、あなたの消費カロリーを知る</title>
  <link rel="canonical" href="<?= h(app_url('')) ?>">
  <link rel="stylesheet" href="landing.css?v=20260723-professional-1">
</head>
<body>
  <header class="site-header">
    <div class="landing-container header-inner">
      <a class="brand" href="./" aria-label="<?= h($appName) ?> トップ">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M5 17.5V12m4.7 5.5V8m4.6 9.5V11m4.7 6.5V5"/></svg>
        </span>
        <span>Body<span>Log</span></span>
      </a>

      <nav class="site-nav" aria-label="メインナビゲーション">
        <a href="#value">選ばれる理由</a>
        <a href="#features">機能</a>
        <a href="#how-to-use">使い方</a>
        <?php if ($isLoggedIn): ?>
          <a class="nav-cta" href="records">記録画面を開く</a>
        <?php else: ?>
          <a class="nav-login" href="login">ログイン</a>
          <a class="nav-cta" href="signup">無料で始める <span>→</span></a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-orb hero-orb-one" aria-hidden="true"></div>
      <div class="hero-orb hero-orb-two" aria-hidden="true"></div>
      <div class="landing-container hero-grid">
        <div class="hero-copy">
          <p class="eyebrow"><span></span> DATA-DRIVEN BODY MANAGEMENT</p>
          <h1>勘ではなく、<br><em>あなたの実績</em>で<br>カロリーを決める。</h1>
          <p class="hero-description">
            体重と摂取カロリーを記録するだけ。実際の体重変化から推定TDEEを算出し、増量・減量に必要なカロリー判断をシンプルにします。
          </p>
          <div class="hero-actions">
            <a class="button button-primary" href="<?= h($primaryHref) ?>"><?= h($primaryLabel) ?> <span>→</span></a>
            <?php if (!$isLoggedIn): ?>
              <a class="button button-ghost" href="login">ログイン</a>
            <?php endif; ?>
          </div>
          <ul class="hero-points" aria-label="利用の特徴">
            <li><span>✓</span> 体重だけでも記録可能</li>
            <li><span>✓</span> すべての基本機能が無料</li>
          </ul>
        </div>

        <div class="dashboard-stage" aria-label="Body Logの分析画面イメージ">
          <div class="stage-label"><span></span> LIVE INSIGHT</div>
          <div class="dashboard-window">
            <div class="dashboard-header">
              <div>
                <small>BODY LOG / OVERVIEW</small>
                <strong>コンディション</strong>
              </div>
              <div class="dashboard-avatar">TK</div>
            </div>

            <div class="metric-grid">
              <div class="metric metric-primary">
                <div class="metric-top"><span>推定TDEE</span><b>更新</b></div>
                <strong>2,648<small> kcal</small></strong>
                <p>直近28日間の実績から算出</p>
              </div>
              <div class="metric">
                <div class="metric-top"><span>7日平均体重</span></div>
                <strong>72.7<small> kg</small></strong>
                <p class="positive">↓ 0.4 kg / 前週比</p>
              </div>
            </div>

            <div class="chart-panel">
              <div class="chart-panel-head">
                <div><small>WEIGHT TREND</small><strong>体重推移</strong></div>
                <div class="chart-legend"><span></span> 7日平均</div>
              </div>
              <svg viewBox="0 0 620 220" role="img" aria-label="緩やかに減少する体重の7日平均グラフ">
                <defs>
                  <linearGradient id="areaFill" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#b9f65b" stop-opacity=".3"/>
                    <stop offset="100%" stop-color="#b9f65b" stop-opacity="0"/>
                  </linearGradient>
                </defs>
                <g class="chart-grid"><line x1="15" y1="38" x2="605" y2="38"/><line x1="15" y1="105" x2="605" y2="105"/><line x1="15" y1="172" x2="605" y2="172"/></g>
                <path class="chart-area" d="M15 47 C65 40 100 70 145 62 S225 82 270 80 S350 117 400 101 S475 138 520 124 S575 157 605 143 L605 193 L15 193 Z"/>
                <path class="chart-path" d="M15 47 C65 40 100 70 145 62 S225 82 270 80 S350 117 400 101 S475 138 520 124 S575 157 605 143"/>
                <g class="chart-dots"><circle cx="15" cy="47" r="5"/><circle cx="145" cy="62" r="5"/><circle cx="270" cy="80" r="5"/><circle cx="400" cy="101" r="5"/><circle cx="520" cy="124" r="5"/><circle cx="605" cy="143" r="6"/></g>
              </svg>
              <div class="chart-dates"><span>6/01</span><span>6/08</span><span>6/15</span><span>6/22</span><span>6/29</span></div>
            </div>

            <div class="target-strip">
              <div><small>現在のペース</small><strong>−0.42 kg / 週</strong></div>
              <div class="target-arrow">→</div>
              <div><small>減量目安カロリー</small><strong>2,180–2,280 kcal</strong></div>
            </div>
          </div>
        </div>
      </div>

      <div class="landing-container trust-row">
        <p>判断に必要な数字を、ひとつの画面に。</p>
        <div><span>01</span> 体重</div><div><span>02</span> 摂取カロリー</div><div><span>03</span> 推定TDEE</div><div><span>04</span> 体重トレンド</div>
      </div>
    </section>

    <section class="value-section" id="value">
      <div class="landing-container">
        <div class="section-heading split-heading">
          <div><p class="eyebrow dark"><span></span> THE BODY LOG METHOD</p><h2>「一般的な目安」から、<br>自分だけの基準へ。</h2></div>
          <p>身長や年齢から出す計算値はスタート地点にすぎません。Body Logは、実際に食べた量と実際に変化した体重を結びつけ、あなたの消費カロリーを継続的にアップデートします。</p>
        </div>

        <div class="method-flow">
          <article><span class="method-number">01</span><div class="method-icon">kg</div><h3>毎日を記録</h3><p>体重と摂取カロリーを、迷わず入力できるシンプルな記録画面。</p></article>
          <div class="flow-arrow" aria-hidden="true">→</div>
          <article><span class="method-number">02</span><div class="method-icon">↗</div><h3>変化を分析</h3><p>日々のブレを7日平均にならし、本当の体重トレンドを捉えます。</p></article>
          <div class="flow-arrow" aria-hidden="true">→</div>
          <article class="method-highlight"><span class="method-number">03</span><div class="method-icon">T</div><h3>TDEEを推定</h3><p>平均摂取カロリーと体重変化から、実績ベースの消費量を逆算。</p></article>
          <div class="flow-arrow" aria-hidden="true">→</div>
          <article><span class="method-number">04</span><div class="method-icon">◎</div><h3>次の目標を決定</h3><p>増量・減量のペースに合わせ、必要な摂取量を判断できます。</p></article>
        </div>

        <div class="formula-card">
          <div><span>BODY LOG FORMULA</span><strong>推定TDEE</strong></div>
          <b>=</b><div><span>DAILY INPUT</span><strong>平均摂取カロリー</strong></div>
          <b>−</b><div><span>ENERGY BALANCE</span><strong>体重変化による収支</strong></div>
        </div>
      </div>
    </section>

    <section class="features-section" id="features">
      <div class="landing-container">
        <div class="section-heading centered">
          <p class="eyebrow dark"><span></span> BUILT FOR CONSISTENCY</p>
          <h2>続けるためにシンプルに。<br>判断するために、正確に。</h2>
          <p>記録の負担を抑えながら、ボディメイクに必要な情報は妥協しません。</p>
        </div>

        <div class="bento-grid">
          <article class="bento-card bento-large">
            <div class="bento-copy"><span class="card-tag">SMART INPUT</span><h3>毎日の記録は、<br>最小限の入力で。</h3><p>体重だけでも保存可能。摂取カロリー・歩数・メモは必要な日に追加できます。</p></div>
            <div class="input-demo"><div><span>今日の体重</span><strong>72.5 <small>kg</small></strong></div><div><span>摂取カロリー</span><strong>2,240 <small>kcal</small></strong></div><button type="button" tabindex="-1">記録する <span>→</span></button></div>
          </article>
          <article class="bento-card bento-dark"><span class="card-tag">7-DAY AVERAGE</span><h3>ノイズではなく、<br>トレンドを見る。</h3><p>水分量や外食で揺れる日々の数値を平均化。進んでいる方向が一目で分かります。</p><div class="trend-number"><strong>−0.42</strong><span>kg / week</span></div></article>
          <article class="bento-card"><span class="card-tag">VISUAL REPORT</span><h3>変化をグラフで確認</h3><p>体重・カロリー・歩数の推移を直感的に把握。</p><div class="mini-bars" aria-hidden="true"><i style="height:44%"></i><i style="height:68%"></i><i style="height:52%"></i><i style="height:85%"></i><i style="height:72%"></i><i style="height:94%"></i><i style="height:80%"></i></div></article>
          <article class="bento-card"><span class="card-tag">YOUR DATA</span><h3>データはいつでも手元に</h3><p>記録はCSV形式で出力可能。自分での分析や長期保管にも使えます。</p><div class="csv-pill">CSV <span>↓</span></div></article>
        </div>
      </div>
    </section>

    <section class="how-section" id="how-to-use">
      <div class="landing-container how-grid">
        <div class="how-intro"><p class="eyebrow dark"><span></span> START IN MINUTES</p><h2>今日から始めて、<br>自分の基準をつくる。</h2><p>難しい初期設定はありません。まずは今日の体重を記録するところから。</p><a class="text-link" href="<?= h($primaryHref) ?>"><?= h($primaryLabel) ?> <span>→</span></a></div>
        <ol class="steps">
          <li><span>01</span><div><h3>無料アカウントを作成</h3><p>ユーザー名とパスワードだけで始められます。</p></div></li>
          <li><span>02</span><div><h3>体重とカロリーを記録</h3><p>毎日の記録を無理のない範囲で積み上げます。</p></div></li>
          <li><span>03</span><div><h3>自分の変化を確認</h3><p>7日平均と推定TDEEを、次のカロリー判断に活用します。</p></div></li>
        </ol>
      </div>
    </section>

    <section class="final-cta">
      <div class="landing-container final-cta-inner">
        <div><p class="eyebrow"><span></span> YOUR DATA. YOUR STANDARD.</p><h2>身体は、記録すれば<br><em>もっと分かる。</em></h2><p>数字に振り回されるボディメイクから、数字を味方につけるボディメイクへ。</p></div>
        <div class="final-action"><a class="button button-primary" href="<?= h($primaryHref) ?>"><?= h($primaryLabel) ?> <span>→</span></a><small>体重だけでも、今日から記録できます。</small></div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="landing-container footer-inner">
      <a class="brand footer-brand" href="./"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 17.5V12m4.7 5.5V8m4.6 9.5V11m4.7 6.5V5"/></svg></span><span>Body<span>Log</span></span></a>
      <p>実績から、あなたの消費カロリーを知る。</p>
      <nav aria-label="フッターナビゲーション"><a href="privacy">プライバシーポリシー</a><a href="terms">利用規約</a></nav>
      <small>© <?= date('Y') ?> Body Log</small>
    </div>
  </footer>
</body>
</html>
