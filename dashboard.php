<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$userId = require_login();
$config = app_config();
$appName = $config['app_name'] ?? '体重・カロリー記録';
$username = $_SESSION['username'] ?? '';

function format_chart_number(?float $value, string $suffix, int $decimals = 0): string
{
    if ($value === null) {
        return '-';
    }

    return number_format($value, $decimals) . ' ' . $suffix;
}

function average_numeric(array $values): ?float
{
    $filtered = array_values(array_filter($values, fn($value) => $value !== null && $value !== ''));

    if (!$filtered) {
        return null;
    }

    return array_sum($filtered) / count($filtered);
}

function rolling_average(array $values, int $window = 7): array
{
    $result = [];

    foreach ($values as $index => $_value) {
        $slice = array_slice($values, max(0, $index - $window + 1), $window);
        $numbers = array_values(array_filter($slice, fn($value) => $value !== null && $value !== ''));

        $result[] = $numbers ? array_sum($numbers) / count($numbers) : null;
    }

    return $result;
}

function date_label(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp ? date('n/j', $timestamp) : str_replace('-', '/', $date);
}

function compact_value_label(float $value, string $unit, int $decimals = 0): string
{
    if ($unit === 'kg') {
        return number_format($value, $decimals) . 'kg';
    }

    if (abs($value) >= 1000) {
        return number_format((int)round($value));
    }

    return number_format($value, $decimals);
}

function should_show_data_label(int $index, int $count): bool
{
    if ($count <= 18) {
        return true;
    }

    if ($count <= 45) {
        return $index % 2 === 0 || $index === $count - 1;
    }

    if ($count <= 90) {
        return $index % 5 === 0 || $index === $count - 1;
    }

    return $index % 10 === 0 || $index === $count - 1;
}

function value_range(array $seriesList, float $paddingRatio = 0.18, bool $startAtZero = false): array
{
    $values = [];

    foreach ($seriesList as $series) {
        foreach ($series['values'] as $value) {
            if ($value !== null && $value !== '') {
                $values[] = (float)$value;
            }
        }
    }

    if (!$values) {
        return [0.0, 1.0];
    }

    $min = min($values);
    $max = max($values);

    if ($startAtZero) {
        $min = 0.0;
    }

    if ($min === $max) {
        $padding = max(1.0, abs($max) * 0.08);
        return [$startAtZero ? 0.0 : $min - $padding, $max + $padding];
    }

    $padding = ($max - $min) * $paddingRatio;

    return [$startAtZero ? 0.0 : $min - $padding, $max + $padding];
}

function chart_x(int $index, int $count, float $left, float $width, float $inset = 18.0): float
{
    if ($count <= 1) {
        return $left + ($width / 2);
    }

    $usableWidth = max(1.0, $width - ($inset * 2));

    return $left + $inset + ($usableWidth * $index / ($count - 1));
}

function chart_y(?float $value, float $min, float $max, float $top, float $height): ?float
{
    if ($value === null) {
        return null;
    }

    if ($max <= $min) {
        return $top + ($height / 2);
    }

    return $top + (($max - $value) / ($max - $min) * $height);
}

function build_svg_path(array $values, float $min, float $max, float $left, float $top, float $width, float $height): string
{
    $count = count($values);
    $path = '';
    $isDrawing = false;

    foreach ($values as $index => $value) {
        $y = chart_y($value === null || $value === '' ? null : (float)$value, $min, $max, $top, $height);

        if ($y === null) {
            $isDrawing = false;
            continue;
        }

        $x = chart_x($index, $count, $left, $width);
        $path .= ($isDrawing ? ' L ' : ' M ') . round($x, 2) . ' ' . round($y, 2);
        $isDrawing = true;
    }

    return trim($path);
}

function render_line_data_labels(array $values, float $min, float $max, float $left, float $top, float $width, float $height, string $unit, int $decimals): string
{
    $count = count($values);
    $labels = '';

    foreach ($values as $index => $value) {
        if ($value === null || $value === '' || !should_show_data_label($index, $count)) {
            continue;
        }

        $x = chart_x($index, $count, $left, $width);
        $y = chart_y((float)$value, $min, $max, $top, $height);

        if ($y === null) {
            continue;
        }

        $x = max($left + 16, min($left + $width - 16, $x));
        $labelY = max($top + 14, $y - 11);

        $labels .= '<text class="chart-value-label chart-line-value-label" x="' . round($x, 2) . '" y="' . round($labelY, 2) . '">' . h(compact_value_label((float)$value, $unit, $decimals)) . '</text>';
    }

    return $labels;
}

function render_line_chart(string $title, array $labels, array $seriesList, string $unit, int $decimals = 1): string
{
    $width = 1000.0;
    $height = 380.0;
    $left = 82.0;
    $right = 42.0;
    $top = 58.0;
    $bottom = 62.0;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;
    [$min, $max] = value_range($seriesList, 0.18);
    $count = count($labels);
    $clipId = 'clip_' . substr(md5($title . serialize($labels)), 0, 10);

    $grid = '';
    for ($i = 0; $i <= 4; $i++) {
        $y = $top + ($plotHeight * $i / 4);
        $grid .= '<line class="chart-grid" x1="' . $left . '" y1="' . round($y, 2) . '" x2="' . ($width - $right) . '" y2="' . round($y, 2) . '" />';
    }

    $paths = '';
    $dataLabels = '';

    foreach ($seriesList as $series) {
        $path = build_svg_path($series['values'], $min, $max, $left, $top, $plotWidth, $plotHeight);

        if ($path !== '') {
            $paths .= '<path class="chart-line ' . h($series['class']) . '" d="' . h($path) . '" />';
        }

        if (!empty($series['show_labels'])) {
            $dataLabels .= render_line_data_labels($series['values'], $min, $max, $left, $top, $plotWidth, $plotHeight, $unit, $decimals);
        }
    }

    $firstLabel = $labels[0] ?? '';
    $lastLabel = $labels[$count - 1] ?? '';
    $maxLabel = number_format($max, $decimals) . ' ' . $unit;
    $minLabel = number_format($min, $decimals) . ' ' . $unit;

    return '<svg class="chart-svg" viewBox="0 0 1000 380" role="img" aria-label="' . h($title) . '">' .
        '<defs><clipPath id="' . h($clipId) . '"><rect x="' . $left . '" y="' . $top . '" width="' . $plotWidth . '" height="' . $plotHeight . '" /></clipPath></defs>' .
        $grid .
        '<line class="chart-axis" x1="' . $left . '" y1="' . ($height - $bottom) . '" x2="' . ($width - $right) . '" y2="' . ($height - $bottom) . '" />' .
        '<line class="chart-axis" x1="' . $left . '" y1="' . $top . '" x2="' . $left . '" y2="' . ($height - $bottom) . '" />' .
        '<text class="chart-label" x="12" y="' . ($top + 5) . '">' . h($maxLabel) . '</text>' .
        '<text class="chart-label" x="12" y="' . ($height - $bottom) . '">' . h($minLabel) . '</text>' .
        '<text class="chart-label" x="' . $left . '" y="' . ($height - 14) . '">' . h($firstLabel) . '</text>' .
        '<text class="chart-label chart-label-end" x="' . ($width - $right) . '" y="' . ($height - 14) . '">' . h($lastLabel) . '</text>' .
        '<g clip-path="url(#' . h($clipId) . ')">' . $paths . '</g>' .
        $dataLabels .
        '</svg>';
}

function render_bar_chart(string $title, array $labels, array $values, string $unit): string
{
    $width = 1000.0;
    $height = 380.0;
    $left = 82.0;
    $right = 42.0;
    $top = 58.0;
    $bottom = 62.0;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;
    $count = count($labels);
    [$min, $max] = value_range([['values' => $values]], 0.18, true);
    $max = max($max, 1.0);
    $slotWidth = $count > 0 ? $plotWidth / $count : $plotWidth;
    $barWidth = max(4.0, min(34.0, $slotWidth * 0.58));
    $clipId = 'clip_' . substr(md5($title . serialize($labels)), 0, 10);

    $grid = '';
    for ($i = 0; $i <= 4; $i++) {
        $y = $top + ($plotHeight * $i / 4);
        $grid .= '<line class="chart-grid" x1="' . $left . '" y1="' . round($y, 2) . '" x2="' . ($width - $right) . '" y2="' . round($y, 2) . '" />';
    }

    $bars = '';
    $dataLabels = '';

    foreach ($values as $index => $value) {
        if ($value === null || $value === '') {
            continue;
        }

        $value = (float)$value;
        $xCenter = $left + ($slotWidth * $index) + ($slotWidth / 2);
        $barHeight = ($value / $max) * $plotHeight;
        $x = $xCenter - ($barWidth / 2);
        $y = $top + $plotHeight - $barHeight;

        $bars .= '<rect class="chart-bar" x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($barWidth, 2) . '" height="' . round($barHeight, 2) . '" rx="5" />';

        if (should_show_data_label($index, $count)) {
            $labelY = max($top + 14, $y - 8);
            $dataLabels .= '<text class="chart-value-label chart-bar-value-label" x="' . round($xCenter, 2) . '" y="' . round($labelY, 2) . '">' . h(compact_value_label($value, $unit, 0)) . '</text>';
        }
    }

    $firstLabel = $labels[0] ?? '';
    $lastLabel = $labels[$count - 1] ?? '';
    $maxLabel = number_format($max, 0) . ' ' . $unit;

    return '<svg class="chart-svg" viewBox="0 0 1000 380" role="img" aria-label="' . h($title) . '">' .
        '<defs><clipPath id="' . h($clipId) . '"><rect x="' . $left . '" y="' . $top . '" width="' . $plotWidth . '" height="' . $plotHeight . '" /></clipPath></defs>' .
        $grid .
        '<line class="chart-axis" x1="' . $left . '" y1="' . ($height - $bottom) . '" x2="' . ($width - $right) . '" y2="' . ($height - $bottom) . '" />' .
        '<line class="chart-axis" x1="' . $left . '" y1="' . $top . '" x2="' . $left . '" y2="' . ($height - $bottom) . '" />' .
        '<text class="chart-label" x="12" y="' . ($top + 5) . '">' . h($maxLabel) . '</text>' .
        '<text class="chart-label" x="12" y="' . ($height - $bottom) . '">0 ' . h($unit) . '</text>' .
        '<text class="chart-label" x="' . $left . '" y="' . ($height - 14) . '">' . h($firstLabel) . '</text>' .
        '<text class="chart-label chart-label-end" x="' . ($width - $right) . '" y="' . ($height - 14) . '">' . h($lastLabel) . '</text>' .
        '<g clip-path="url(#' . h($clipId) . ')">' . $bars . '</g>' .
        $dataLabels .
        '</svg>';
}

$stmt = db()->prepare("
    SELECT log_date, weight_kg, calories, steps
    FROM logs
    WHERE user_id = :user_id
    ORDER BY log_date ASC
");
$stmt->execute([':user_id' => $userId]);
$allLogs = $stmt->fetchAll();
$chartLogs = array_slice($allLogs, -120);

$labels = array_map(fn($log) => date_label((string)$log['log_date']), $chartLogs);
$weightValues = array_map(fn($log) => (float)$log['weight_kg'], $chartLogs);
$weightAverageValues = rolling_average($weightValues, 7);
$calorieValues = array_map(fn($log) => $log['calories'] === null ? null : (float)$log['calories'], $chartLogs);
$stepValues = array_map(fn($log) => $log['steps'] === null ? null : (float)$log['steps'], $chartLogs);

$recentWeightAverage = average_numeric(array_slice($weightValues, -7));
$recentCaloriesAverage = average_numeric(array_slice($calorieValues, -7));
$recentStepsAverage = average_numeric(array_slice($stepValues, -7));
$latestLogDate = $chartLogs ? str_replace('-', '/', (string)$chartLogs[count($chartLogs) - 1]['log_date']) : '-';
$flash = flash_get();

$pageTitle = '経過グラフ';
$pageEyebrow = 'Body Log';
$pageDescription = '体重・摂取カロリー・歩数の推移を見える化します。';
$pageActiveNav = 'progress';
$pageAppClass = 'dashboard-app';

require __DIR__ . '/app/partials/app_header.php';
?>

<?php if ($flash): ?>
      <p class="alert success"><?= h($flash) ?></p>
    <?php endif; ?>

    <?php if (!$chartLogs): ?>
      <section class="card">
        <p class="empty is-visible">まだグラフに表示できる記録がありません。</p>
      </section>
    <?php else: ?>
      <section class="summary-grid" aria-label="グラフサマリー">
        <div class="summary-card">
          <span>表示期間</span>
          <strong><?= h((string)count($chartLogs)) ?>件</strong>
        </div>
        <div class="summary-card">
          <span>最新日</span>
          <strong><?= h($latestLogDate) ?></strong>
        </div>
        <div class="summary-card">
          <span>直近平均体重</span>
          <strong><?= h(format_chart_number($recentWeightAverage, 'kg', 1)) ?></strong>
        </div>
        <div class="summary-card">
          <span>直近平均カロリー</span>
          <strong><?= h(format_chart_number($recentCaloriesAverage, 'kcal')) ?></strong>
        </div>
        <div class="summary-card">
          <span>直近平均歩数</span>
          <strong><?= h(format_chart_number($recentStepsAverage, '歩')) ?></strong>
        </div>
      </section>

      <section class="card chart-card">
        <div class="section-title">
          <div>
            <h2>体重推移</h2>
            <p class="list-meta">日々の体重と7日移動平均を表示します。青線にはデータラベルを表示しています。</p>
          </div>
        </div>
        <div class="chart-legend">
          <span><i class="legend-line weight-line"></i>体重</span>
          <span><i class="legend-line average-line"></i>7日平均</span>
        </div>
        <?= render_line_chart('体重推移', $labels, [
            ['label' => '体重', 'class' => 'chart-line-weight', 'values' => $weightValues, 'show_labels' => true],
            ['label' => '7日平均', 'class' => 'chart-line-average', 'values' => $weightAverageValues, 'show_labels' => false],
        ], 'kg', 1) ?>
      </section>

      <section class="card chart-card">
        <div class="section-title">
          <div>
            <h2>摂取カロリー推移</h2>
            <p class="list-meta">未入力の日はグラフ上では空白になります。バーの上に入力値を表示します。</p>
          </div>
        </div>
        <?= render_bar_chart('摂取カロリー推移', $labels, $calorieValues, 'kcal') ?>
      </section>

      <section class="card chart-card">
        <div class="section-title">
          <div>
            <h2>歩数推移</h2>
            <p class="list-meta">日々の活動量の変化を確認できます。バーの上に入力値を表示します。</p>
          </div>
        </div>
        <?= render_bar_chart('歩数推移', $labels, $stepValues, '歩') ?>
      </section>
    <?php endif; ?>

<?php require __DIR__ . '/app/partials/app_footer.php'; ?>
