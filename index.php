<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/error.php';
require __DIR__ . '/app/metrics.php';

$userId = require_login();
$config = app_config();
$appName = $config['app_name'] ?? '体重・カロリー記録';

function parse_log_input(): array
{
    $date = trim((string)($_POST['log_date'] ?? ''));
    $weightRaw = trim((string)($_POST['weight_kg'] ?? ''));
    $calorieRaw = trim((string)($_POST['calories'] ?? ''));
    $stepsRaw = trim((string)($_POST['steps'] ?? ''));
    $memo = trim((string)($_POST['memo'] ?? ''));

    $dateOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
    $weightOk = is_numeric($weightRaw) && (float)$weightRaw > 0;
    $caloriesOk = $calorieRaw === '' || ctype_digit($calorieRaw);
    $stepsOk = $stepsRaw === '' || ctype_digit($stepsRaw);

    return [
        'date' => $date,
        'weight' => $weightOk ? (float)$weightRaw : 0.0,
        'calories' => $calorieRaw === '' ? null : (int)$calorieRaw,
        'steps' => $stepsRaw === '' ? null : (int)$stepsRaw,
        'memo' => function_exists('mb_substr') ? mb_substr($memo, 0, 1000) : substr($memo, 0, 1000),
        'valid' => $dateOk && $weightOk && $caloriesOk && $stepsOk,
    ];
}

function format_calories_value($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return number_format((int)$value) . ' kcal';
}

function format_steps_value($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return number_format((int)$value) . ' 歩';
}

function format_kcal_summary(?float $value): string
{
    if ($value === null) {
        return '-';
    }

    return number_format((int)round($value)) . ' kcal';
}

function format_steps_summary(?float $value): string
{
    if ($value === null) {
        return '-';
    }

    return number_format((int)round($value)) . ' 歩';
}

function format_weekly_weight_trend(?float $dailyTrendKg): string
{
    if ($dailyTrendKg === null) {
        return '-';
    }

    $weeklyTrendKg = $dailyTrendKg * 7;

    if (abs($weeklyTrendKg) < 0.005) {
        return '±0.00 kg/週';
    }

    $prefix = $weeklyTrendKg > 0 ? '+' : '';
    return $prefix . number_format($weeklyTrendKg, 2) . ' kg/週';
}

function average_from_logs(array $logs, string $key, bool $requireAll = false): ?float
{
    $values = [];

    foreach ($logs as $log) {
        if (!array_key_exists($key, $log) || $log[$key] === null || $log[$key] === '') {
            if ($requireAll) {
                return null;
            }
            continue;
        }

        $values[] = (float)$log[$key];
    }

    if (count($values) === 0) {
        return null;
    }

    return array_sum($values) / count($values);
}

/**
 * 実際の日付をX軸、体重をY軸として単回帰し、1日あたりの体重変化を返す。
 */
function calculate_weight_trend_per_day(array $logs): ?float
{
    if (count($logs) < 2) {
        return null;
    }

    usort($logs, fn(array $a, array $b): int => strcmp((string)$a['log_date'], (string)$b['log_date']));

    $startDate = new DateTimeImmutable((string)$logs[0]['log_date']);
    $points = [];

    foreach ($logs as $log) {
        $date = new DateTimeImmutable((string)$log['log_date']);
        $points[] = [
            'x' => (float)$startDate->diff($date)->days,
            'y' => (float)$log['weight_kg'],
        ];
    }

    $count = count($points);
    $meanX = array_sum(array_column($points, 'x')) / $count;
    $meanY = array_sum(array_column($points, 'y')) / $count;
    $numerator = 0.0;
    $denominator = 0.0;

    foreach ($points as $point) {
        $xDiff = $point['x'] - $meanX;
        $numerator += $xDiff * ($point['y'] - $meanY);
        $denominator += $xDiff ** 2;
    }

    if ($denominator <= 0) {
        return null;
    }

    return $numerator / $denominator;
}

function calculate_bmr(float $weightKg, array $profile): ?float
{
    if (!$profile['is_complete'] || $weightKg <= 0) {
        return null;
    }

    $base = 10 * $weightKg + 6.25 * (float)$profile['height_cm'] - 5 * (int)$profile['age'];

    return $profile['sex'] === 'female' ? $base - 161 : $base + 5;
}

function redirect_index_with_page(int $page): never
{
    redirect('index.php?page=' . max(1, $page));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string)($_POST['action'] ?? '');
    $returnPage = max(1, (int)($_POST['page'] ?? 1));

    try {
        if ($action === 'save') {
            $input = parse_log_input();

            if (!$input['valid']) {
                flash_set('日付・体重を正しく入力してください。カロリーと歩数は任意です。');
                redirect('index.php');
            }

            $stmt = db()->prepare("
                INSERT INTO logs (user_id, log_date, weight_kg, calories, steps, memo)
                VALUES (:user_id, :log_date, :weight_kg, :calories, :steps, :memo)
                ON DUPLICATE KEY UPDATE
                    weight_kg = VALUES(weight_kg),
                    calories = VALUES(calories),
                    steps = VALUES(steps),
                    memo = VALUES(memo),
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':log_date' => $input['date'],
                ':weight_kg' => $input['weight'],
                ':calories' => $input['calories'],
                ':steps' => $input['steps'],
                ':memo' => $input['memo'],
            ]);

            flash_set('記録を保存しました。同じ日付がある場合は上書きしています。');
            redirect('index.php');
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $input = parse_log_input();

            if ($id <= 0 || !$input['valid']) {
                flash_set('日付・体重を正しく入力してください。カロリーと歩数は任意です。');
                redirect_index_with_page($returnPage);
            }

            $stmt = db()->prepare("
                UPDATE logs
                SET
                    log_date = :log_date,
                    weight_kg = :weight_kg,
                    calories = :calories,
                    steps = :steps,
                    memo = :memo,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND user_id = :user_id
            ");

            $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
                ':log_date' => $input['date'],
                ':weight_kg' => $input['weight'],
                ':calories' => $input['calories'],
                ':steps' => $input['steps'],
                ':memo' => $input['memo'],
            ]);

            flash_set('記録を更新しました。');
            redirect_index_with_page($returnPage);
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);

            $stmt = db()->prepare("DELETE FROM logs WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
            ]);

            flash_set('記録を削除しました。');
            redirect_index_with_page($returnPage);
        }

        if ($action === 'clear_all') {
            $stmt = db()->prepare("DELETE FROM logs WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);

            flash_set('すべての記録を削除しました。');
            redirect('index.php');
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            flash_set('同じ日付の記録が既にあります。日付を変えるか、その日の記録を編集してください。');
        } else {
            report_app_exception($e, 'records.database');
            flash_set(public_error_message());
        }
        redirect('index.php');
    } catch (Throwable $e) {
        report_app_exception($e, 'records.unexpected');
        flash_set(public_error_message());
        redirect('index.php');
    }
}

$perPage = 30;

$countStmt = db()->prepare("SELECT COUNT(*) FROM logs WHERE user_id = :user_id");
$countStmt->execute([':user_id' => $userId]);
$count = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($count / $perPage));
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$showingFrom = $count > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $perPage, $count);

// TDEEでは直近28暦日を使うため、余裕を持って60件取得する。
$recentStmt = db()->prepare("
    SELECT id, log_date, weight_kg, calories, steps, memo
    FROM logs
    WHERE user_id = :user_id
    ORDER BY log_date DESC
    LIMIT 60
");
$recentStmt->execute([':user_id' => $userId]);
$recentLogs = $recentStmt->fetchAll();

$stmt = db()->prepare("
    SELECT id, log_date, weight_kg, calories, steps, memo
    FROM logs
    WHERE user_id = :user_id
    ORDER BY log_date DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute([':user_id' => $userId]);
$logs = $stmt->fetchAll();

$userProfile = get_user_profile($userId);
$profileIsComplete = (bool)$userProfile['is_complete'];

$latestWeightKg = $count > 0 ? (float)$recentLogs[0]['weight_kg'] : 0.0;
$latestWeight = $count > 0 ? number_format($latestWeightKg, 1) . ' kg' : '-';
$latestCalories = $count > 0 ? format_calories_value($recentLogs[0]['calories']) : '-';
$latestSteps = $count > 0 ? format_steps_value($recentLogs[0]['steps']) : '-';
$latestWeightValue = $count > 0 ? number_format($latestWeightKg, 1, '.', '') : '';

/**
 * 7日平均・前週比
 *
 * - 0件：平均体重は「-」
 * - 1〜6件：記録済み平均として表示
 * - 7件以上：7日平均として表示
 * - 14件未満：前週比は「あと○日」
 * - 14件以上：直近7件平均 - その前の7件平均
 */
$averageWeightLabel = '平均体重';
$averageWeightValue = '-';
$recentAvgWeight = null;

if ($count > 0) {
    $recentDays = min(7, $count);
    $recentLogsForAverage = array_slice($recentLogs, 0, $recentDays);
    $recentAvgWeight = array_sum(array_map(fn($log) => (float)$log['weight_kg'], $recentLogsForAverage)) / $recentDays;

    if ($count >= 7) {
        $averageWeightLabel = '7日平均体重';
        $averageWeightValue = number_format($recentAvgWeight, 1) . ' kg';
    } else {
        $averageWeightLabel = '平均体重';
        $averageWeightValue = number_format($recentAvgWeight, 1) . ' kg（' . $recentDays . '日分）';
    }
}

$weekDiffLabel = '前週比';
$weekDiffValue = '-';
$weekDiffClass = '';
$currentWeekAvgWeight = null;
$previousWeekAvgWeight = null;
$currentWeekLogs = [];
$previousWeekLogs = [];

if ($count >= 14) {
    $currentWeekLogs = array_slice($recentLogs, 0, 7);
    $previousWeekLogs = array_slice($recentLogs, 7, 7);

    $currentWeekAvgWeight = array_sum(array_map(fn($log) => (float)$log['weight_kg'], $currentWeekLogs)) / 7;
    $previousWeekAvgWeight = array_sum(array_map(fn($log) => (float)$log['weight_kg'], $previousWeekLogs)) / 7;
    $weekDiff = $currentWeekAvgWeight - $previousWeekAvgWeight;

    if ($weekDiff > 0) {
        $weekDiffValue = '+' . number_format($weekDiff, 1) . ' kg';
        $weekDiffClass = 'positive';
    } elseif ($weekDiff < 0) {
        $weekDiffValue = number_format($weekDiff, 1) . ' kg';
        $weekDiffClass = 'negative';
    } else {
        $weekDiffValue = '±0.0 kg';
        $weekDiffClass = 'neutral';
    }
} elseif ($count > 0) {
    $weekDiffValue = 'あと' . (14 - $count) . '日で表示';
}

$averageCaloriesLabel = '7日平均カロリー';
$averageCaloriesValue = '-';
$averageStepsLabel = '7日平均歩数';
$averageStepsValue = '-';

if ($count > 0) {
    $recentDays = min(7, $count);
    $recentLogsForAverage = array_slice($recentLogs, 0, $recentDays);

    $avgCalories = average_from_logs($recentLogsForAverage, 'calories');
    if ($avgCalories !== null) {
        $calorieInputDays = count(array_filter($recentLogsForAverage, fn($log) => $log['calories'] !== null && $log['calories'] !== ''));
        $averageCaloriesValue = format_kcal_summary($avgCalories) . ($calorieInputDays < 7 ? '（' . $calorieInputDays . '日分）' : '');
    }

    $avgSteps = average_from_logs($recentLogsForAverage, 'steps');
    if ($avgSteps !== null) {
        $stepsInputDays = count(array_filter($recentLogsForAverage, fn($log) => $log['steps'] !== null && $log['steps'] !== ''));
        $averageStepsValue = format_steps_summary($avgSteps) . ($stepsInputDays < 7 ? '（' . $stepsInputDays . '日分）' : '');
    }
}

$estimatedBmr = calculate_bmr($latestWeightKg, $userProfile);
$estimatedBmrValue = format_kcal_summary($estimatedBmr);

/**
 * 推定TDEE
 *
 * - 最新記録日を基準に直近28暦日のデータを使用
 * - 実際の日付と体重に回帰直線を当て、1日あたりの体重トレンドを算出
 * - 推定TDEE = 平均摂取カロリー - (1日あたりの体重変化 × 7,700)
 * - 14〜27日間は暫定値、28日間そろうと通常の推定値として表示
 */
$tdeeWindowDays = 28;
$tdeeMinimumSpanDays = 14;
$tdeeLogs = [];
$tdeeSpanDays = 0;
$tdeeWeightInputDays = 0;
$tdeeCalorieInputDays = 0;
$tdeeAverageCalories = null;
$tdeeWeightTrendPerDay = null;
$tdeeRequiredWeightDays = 0;
$tdeeRequiredCalorieDays = 0;
$tdeeCanCalculate = false;
$tdeeIsProvisional = false;
$tdeeStatusValue = '算出中';
$tdeeConfidenceValue = '-';
$tdeePeriodValue = '-';
$tdeeDataValue = '-';

if ($count > 0) {
    $latestLogDate = new DateTimeImmutable((string)$recentLogs[0]['log_date']);
    $tdeeWindowStart = $latestLogDate->modify('-' . ($tdeeWindowDays - 1) . ' days');

    foreach ($recentLogs as $log) {
        $logDate = new DateTimeImmutable((string)$log['log_date']);
        if ($logDate >= $tdeeWindowStart && $logDate <= $latestLogDate) {
            $tdeeLogs[] = $log;
        }
    }

    usort($tdeeLogs, fn(array $a, array $b): int => strcmp((string)$a['log_date'], (string)$b['log_date']));
    $tdeeWeightInputDays = count($tdeeLogs);

    if ($tdeeWeightInputDays > 0) {
        $earliestTdeeDate = new DateTimeImmutable((string)$tdeeLogs[0]['log_date']);
        $latestTdeeDate = new DateTimeImmutable((string)$tdeeLogs[$tdeeWeightInputDays - 1]['log_date']);
        $tdeeSpanDays = $earliestTdeeDate->diff($latestTdeeDate)->days + 1;
        $tdeePeriodValue = $tdeeSpanDays . '日間';
    }

    $tdeeCalorieInputDays = count(array_filter(
        $tdeeLogs,
        fn($log) => $log['calories'] !== null && $log['calories'] !== ''
    ));
    $tdeeAverageCalories = average_from_logs($tdeeLogs, 'calories');
    $tdeeWeightTrendPerDay = calculate_weight_trend_per_day($tdeeLogs);
    $tdeeDataValue = '体重' . $tdeeWeightInputDays . '日・食事' . $tdeeCalorieInputDays . '日';

    if ($tdeeSpanDays < $tdeeMinimumSpanDays) {
        $tdeeStatusValue = 'あと' . ($tdeeMinimumSpanDays - $tdeeSpanDays) . '日';
    } else {
        $tdeeIsProvisional = $tdeeSpanDays < $tdeeWindowDays;
        $tdeeRequiredWeightDays = $tdeeIsProvisional ? 10 : 21;
        $tdeeRequiredCalorieDays = $tdeeIsProvisional
            ? max(10, (int)ceil($tdeeSpanDays * 0.75))
            : 24;

        $tdeeCanCalculate = $tdeeWeightInputDays >= $tdeeRequiredWeightDays
            && $tdeeCalorieInputDays >= $tdeeRequiredCalorieDays
            && $tdeeAverageCalories !== null
            && $tdeeWeightTrendPerDay !== null;

        if ($tdeeCanCalculate) {
            $tdeeStatusValue = $tdeeIsProvisional ? '暫定値' : '算出済み';

            if ($tdeeIsProvisional) {
                $tdeeConfidenceValue = '低';
            } elseif ($tdeeWeightInputDays >= 26 && $tdeeCalorieInputDays >= 26) {
                $tdeeConfidenceValue = '高';
            } else {
                $tdeeConfidenceValue = '中';
            }
        } else {
            $missingWeightDays = max(0, $tdeeRequiredWeightDays - $tdeeWeightInputDays);
            $missingCalorieDays = max(0, $tdeeRequiredCalorieDays - $tdeeCalorieInputDays);

            if ($missingCalorieDays > 0) {
                $tdeeStatusValue = '食事記録あと' . $missingCalorieDays . '日';
            } elseif ($missingWeightDays > 0) {
                $tdeeStatusValue = '体重記録あと' . $missingWeightDays . '日';
            } else {
                $tdeeStatusValue = '算出不可';
            }
        }
    }
}

$estimatedTdee = $tdeeCanCalculate
    ? $tdeeAverageCalories - ($tdeeWeightTrendPerDay * 7700)
    : null;
$estimatedTdeeValue = format_kcal_summary($estimatedTdee);
$estimatedTdeeLabel = $tdeeIsProvisional && $estimatedTdee !== null ? '暫定TDEE' : '推定TDEE';
$tdeeAverageCaloriesValue = format_kcal_summary($tdeeAverageCalories);
$tdeeWeightTrendValue = format_weekly_weight_trend($tdeeWeightTrendPerDay);
$activityDiffValue = ($estimatedTdee !== null && $estimatedBmr !== null)
    ? format_kcal_summary($estimatedTdee - $estimatedBmr)
    : '-';

$goal = get_active_goal($userId);
$goalMetrics = $goal ? get_goal_metrics($userId, $goal) : null;

$today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('Y-m-d');
$flash = flash_get();
$username = $_SESSION['username'] ?? '';

$editLog = null;
$editId = (int)($_GET['edit'] ?? 0);

if ($editId > 0) {
    $stmt = db()->prepare("SELECT id, log_date, weight_kg, calories, steps, memo FROM logs WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $editId,
        ':user_id' => $userId,
    ]);
    $editLog = $stmt->fetch() ?: null;
}

$formAction = $editLog ? 'update' : 'save';
$formTitle = $editLog ? '記録を編集' : '今日の記録を入力';
$formButton = $editLog ? '更新する' : '保存する';
$formDate = $editLog ? (string)$editLog['log_date'] : $today;
$formWeight = $editLog ? number_format((float)$editLog['weight_kg'], 1, '.', '') : $latestWeightValue;
$formCalories = ($editLog && $editLog['calories'] !== null) ? (string)(int)$editLog['calories'] : '';
$formSteps = ($editLog && $editLog['steps'] !== null) ? (string)(int)$editLog['steps'] : '';
$formMemo = $editLog ? (string)($editLog['memo'] ?? '') : '';

$pageTitle = '記録';
$pageEyebrow = 'Body Log';
$pageDescription = '日付・体重・カロリー・歩数・メモをシンプルに記録します。';
$pageActiveNav = 'records';
$pageAppClass = '';

require __DIR__ . '/app/partials/app_header.php';
?>

<?php if ($flash): ?>
      <p class="alert success"><?= h($flash) ?></p>
    <?php endif; ?>

    <section class="card">
      <div class="form-head">
        <h2><?= h($formTitle) ?></h2>
        <?php if ($editLog): ?>
          <a class="secondary-link" href="index.php?page=<?= h((string)$currentPage) ?>">新規入力に戻る</a>
        <?php endif; ?>
      </div>

      <form method="post" class="form log-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= h($formAction) ?>">
        <input type="hidden" name="page" value="<?= h((string)$currentPage) ?>">
        <?php if ($editLog): ?>
          <input type="hidden" name="id" value="<?= h((string)$editLog['id']) ?>">
        <?php endif; ?>

        <div class="field">
          <label for="log_date">日付</label>
          <input type="date" id="log_date" name="log_date" value="<?= h($formDate) ?>" required>
        </div>

        <div class="field weight-field">
          <label for="weight_kg">体重 <span>kg</span></label>
          <input type="number" id="weight_kg" name="weight_kg" inputmode="decimal" step="0.1" min="0" value="<?= h($formWeight) ?>" autocomplete="off" required>
        </div>

        <div class="field calories-field">
          <label for="calories">摂取カロリー <span>kcal・任意</span></label>
          <input type="number" id="calories" name="calories" inputmode="numeric" step="1" min="0" placeholder="未入力でもOK" value="<?= h($formCalories) ?>" autocomplete="off">
        </div>

        <div class="field steps-field">
          <label for="steps">歩数 <span>任意</span></label>
          <input type="number" id="steps" name="steps" inputmode="numeric" step="1" min="0" placeholder="例：8500" value="<?= h($formSteps) ?>" autocomplete="off">
        </div>

        <div class="form-actions">
          <button type="submit" class="primary-button"><?= h($formButton) ?></button>
        </div>

        <div class="field memo-field">
          <label for="memo">メモ <span>任意</span></label>
          <textarea id="memo" name="memo" rows="3" maxlength="1000" placeholder="例：外食、むくみ、体調など"><?= h($formMemo) ?></textarea>
        </div>
      </form>
    </section>

    <section class="card compact-goal-card">
      <?php if ($goal && $goalMetrics): ?>
        <div>
          <span class="goal-kicker">目標 <?= h(number_format((float)$goal['target_weight_kg'], 1)) ?>kg</span>
          <h2>あと<?= h(number_format((float)$goalMetrics['remaining_weight'], 1)) ?>kg・期限まで<?= h((string)max(0, (int)$goalMetrics['remaining_days'])) ?>日</h2>
          <p><?= h((string)$goalMetrics['status']) ?></p>
        </div>
        <a class="secondary-link" href="progress">進捗を見る →</a>
      <?php else: ?>
        <div><h2>目標を設定</h2><p>目標体重と目標日を設定できます。</p></div>
        <a class="secondary-link" href="progress">目標を設定する →</a>
      <?php endif; ?>
    </section>

    <section class="summary-grid summary-grid-main" aria-label="サマリー">
      <div class="summary-card">
        <span>最新体重</span>
        <strong><?= h($latestWeight) ?></strong>

        <details class="metric-explanation">
          <summary>算出方法を見る</summary>
          <div class="metric-explanation-body">
            <p>登録されている記録のうち、最も新しい日付の体重を表示しています。</p>
          </div>
        </details>
      </div>

      <div class="summary-card">
        <span><?= h($averageWeightLabel) ?></span>
        <strong><?= h($averageWeightValue) ?></strong>

        <details class="metric-explanation">
          <summary>算出方法を見る</summary>
          <div class="metric-explanation-body">
            <p>直近7件の体重を合計し、記録件数で割った平均です。7件未満の場合は、登録済みの記録だけで計算します。</p>
            <p class="metric-formula">平均体重 ＝ 直近の体重合計 ÷ 使用した記録件数</p>
          </div>
        </details>
      </div>

      <div class="summary-card">
        <span><?= h($weekDiffLabel) ?></span>
        <strong class="<?= h($weekDiffClass) ?>"><?= h($weekDiffValue) ?></strong>

        <details class="metric-explanation">
          <summary>算出方法を見る</summary>
          <div class="metric-explanation-body">
            <p>直近7件の平均体重と、その前7件の平均体重を比較しています。マイナスは減少、プラスは増加を表します。</p>
            <p class="metric-formula">前週比 ＝ 直近7件の平均体重 − その前7件の平均体重</p>
          </div>
        </details>
      </div>

      <div class="summary-card summary-card-tdee">
        <span>推定TDEE</span>
        <strong><?= h($estimatedTdeeValue) ?></strong>

        <div class="metric-card-meta">
          <small>
            <?= h($tdeeStatusValue) ?>
            <?php if ($tdeeConfidenceValue !== '-'): ?>
              ・信頼度 <?= h($tdeeConfidenceValue) ?>
            <?php endif; ?>
          </small>
          <?php if ($tdeeSpanDays > 0): ?>
            <small><?= h((string)$tdeeSpanDays) ?>日間／食事記録<?= h((string)$tdeeCalorieInputDays) ?>日</small>
          <?php endif; ?>
        </div>

        <details class="metric-explanation">
          <summary>算出方法を見る</summary>
          <div class="metric-explanation-body">
            <p>記録された摂取カロリーと体重の変化から、1日の消費カロリーを逆算しています。活動係数は使用していません。</p>
            <p class="metric-formula">推定TDEE ＝ 期間平均摂取カロリー −（1日あたりの体重変化 × 7,700）</p>

            <?php if ($tdeeSpanDays > 0): ?>
              <ul class="metric-breakdown">
                <li><span>期間平均摂取カロリー</span><strong><?= h($tdeeAverageCaloriesValue) ?></strong></li>
                <li><span>体重トレンド</span><strong><?= h($tdeeWeightTrendValue) ?></strong></li>
                <li><span>算出期間</span><strong><?= h((string)$tdeeSpanDays) ?>日間</strong></li>
                <li><span>使用データ</span><strong>体重<?= h((string)$tdeeWeightInputDays) ?>日・食事<?= h((string)$tdeeCalorieInputDays) ?>日</strong></li>
              </ul>
            <?php endif; ?>

            <p>水分量や食事記録の誤差で数値は変動します。安定した推定には28日以上の継続記録を推奨します。</p>
          </div>
        </details>
      </div>

      <div class="summary-card">
        <span><?= h($averageCaloriesLabel) ?></span>
        <strong><?= h($averageCaloriesValue) ?></strong>

        <details class="metric-explanation">
          <summary>算出方法を見る</summary>
          <div class="metric-explanation-body">
            <p>直近7件のうち、摂取カロリーが入力されている日の平均です。未入力日は0kcalとして扱わず、平均から除外します。</p>
            <p class="metric-formula">平均摂取カロリー ＝ 入力済みカロリーの合計 ÷ 入力日数</p>
          </div>
        </details>
      </div>

      <div class="summary-card">
        <span><?= h($averageStepsLabel) ?></span>
        <strong><?= h($averageStepsValue) ?></strong>

        <details class="metric-explanation">
          <summary>算出方法を見る</summary>
          <div class="metric-explanation-body">
            <p>直近7件のうち、歩数が入力されている日の平均です。未入力日は0歩として扱わず、平均から除外します。</p>
            <p class="metric-formula">平均歩数 ＝ 入力済み歩数の合計 ÷ 入力日数</p>
          </div>
        </details>
      </div>

      <div class="summary-card">
        <span>推定基礎代謝</span>
        <strong><?= h($estimatedBmrValue) ?></strong>

        <details class="metric-explanation">
          <summary>算出方法を見る</summary>
          <div class="metric-explanation-body">
            <p>最新体重と、アカウント設定の身長・年齢・性別から、ミフリン・セントジオール式で推定しています。</p>
            <p class="metric-formula">男性：10×体重＋6.25×身長−5×年齢＋5<br>女性：10×体重＋6.25×身長−5×年齢−161</p>
          </div>
        </details>
      </div>

      <div class="summary-card">
        <span>体重トレンド</span>
        <strong><?= h($tdeeWeightTrendValue) ?></strong>

        <details class="metric-explanation">
          <summary>算出方法を見る</summary>
          <div class="metric-explanation-body">
            <p>TDEE算出期間中の体重と実際の日付に回帰直線を当て、1日あたりの変化量を週単位に換算しています。</p>
            <p>マイナスは減少傾向、プラスは増加傾向を表します。単純な最初と最後の差ではありません。</p>
          </div>
        </details>
      </div>
    </section>

    <section class="card">
      <div class="section-title">
        <div>
          <h2>記録一覧</h2>
          <?php if ($count > 0): ?>
            <p class="list-meta"><?= h((string)$showingFrom) ?>〜<?= h((string)$showingTo) ?>件目 / 全<?= h((string)$count) ?>件</p>
          <?php endif; ?>
        </div>

        <?php if ($count > 0): ?>
          <form method="post" onsubmit="return confirm('すべての記録を削除しますか？');">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="text-button">全削除</button>
          </form>
        <?php endif; ?>
      </div>

      <?php if ($count === 0): ?>
        <p class="empty is-visible">まだ記録がありません。</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>日付</th>
                <th>体重</th>
                <th>摂取カロリー</th>
                <th>歩数</th>
                <th>メモ</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <?php $memoText = trim((string)($log['memo'] ?? '')); ?>
                <tr>
                  <td><?= h(str_replace('-', '/', (string)$log['log_date'])) ?></td>
                  <td><?= h(number_format((float)$log['weight_kg'], 1)) ?> kg</td>
                  <td><?= h(format_calories_value($log['calories'])) ?></td>
                  <td><?= h(format_steps_value($log['steps'])) ?></td>
                  <td class="memo-cell"><?= $memoText !== '' ? nl2br(h($memoText)) : '<span class="muted">-</span>' ?></td>
                  <td>
                    <div class="row-actions">
                      <a class="edit-link" href="index.php?page=<?= h((string)$currentPage) ?>&edit=<?= h((string)$log['id']) ?>">編集</a>
                      <form method="post" onsubmit="return confirm('この記録を削除しますか？');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="page" value="<?= h((string)$currentPage) ?>">
                        <input type="hidden" name="id" value="<?= h((string)$log['id']) ?>">
                        <button type="submit" class="delete-button">削除</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav class="pagination" aria-label="ページ送り">
            <?php if ($currentPage > 1): ?>
              <a class="page-link" href="index.php?page=<?= h((string)($currentPage - 1)) ?>">前へ</a>
            <?php else: ?>
              <span class="page-link is-disabled">前へ</span>
            <?php endif; ?>

            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
              <?php if ($page === $currentPage): ?>
                <span class="page-link is-current"><?= h((string)$page) ?></span>
              <?php else: ?>
                <a class="page-link" href="index.php?page=<?= h((string)$page) ?>"><?= h((string)$page) ?></a>
              <?php endif; ?>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
              <a class="page-link" href="index.php?page=<?= h((string)($currentPage + 1)) ?>">次へ</a>
            <?php else: ?>
              <span class="page-link is-disabled">次へ</span>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </section>

<?php require __DIR__ . '/app/partials/app_footer.php'; ?>
