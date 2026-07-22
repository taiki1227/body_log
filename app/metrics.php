<?php
declare(strict_types=1);

function goal_today(): DateTimeImmutable
{
    return new DateTimeImmutable('today', new DateTimeZone('Asia/Tokyo'));
}

function get_recent_average_weight(int $userId, int $limit = 7): ?float
{
    $stmt = db()->prepare("SELECT weight_kg FROM logs WHERE user_id = :user_id ORDER BY log_date DESC, id DESC LIMIT " . max(1, $limit));
    $stmt->execute([':user_id' => $userId]);
    $values = array_map('floatval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    return $values ? array_sum($values) / count($values) : null;
}

function get_active_goal(int $userId): ?array
{
    $stmt = db()->prepare("SELECT * FROM goals WHERE user_id = :user_id AND status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    $goal = $stmt->fetch();
    return $goal ?: null;
}

function create_goal(int $userId, float $targetWeightKg, string $targetDate): void
{
    $startWeight = get_recent_average_weight($userId);
    if ($startWeight === null) {
        throw new InvalidArgumentException('目標を設定する前に、体重を1件以上記録してください。');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $cancel = $pdo->prepare("UPDATE goals SET status = 'cancelled' WHERE user_id = :user_id AND status = 'active'");
        $cancel->execute([':user_id' => $userId]);
        $insert = $pdo->prepare("INSERT INTO goals (user_id, start_date, start_weight_kg, target_weight_kg, target_date) VALUES (:user_id, :start_date, :start_weight, :target_weight, :target_date)");
        $insert->execute([
            ':user_id' => $userId,
            ':start_date' => goal_today()->format('Y-m-d'),
            ':start_weight' => round($startWeight, 1),
            ':target_weight' => $targetWeightKg,
            ':target_date' => $targetDate,
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function update_active_goal(int $userId, float $targetWeightKg, string $targetDate): bool
{
    $stmt = db()->prepare("UPDATE goals SET target_weight_kg = :target_weight, target_date = :target_date WHERE user_id = :user_id AND status = 'active'");
    $stmt->execute([':target_weight' => $targetWeightKg, ':target_date' => $targetDate, ':user_id' => $userId]);
    return $stmt->rowCount() > 0;
}

function cancel_active_goal(int $userId): bool
{
    $stmt = db()->prepare("UPDATE goals SET status = 'cancelled' WHERE user_id = :user_id AND status = 'active'");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->rowCount() > 0;
}

function complete_active_goal(int $userId): bool
{
    $stmt = db()->prepare("UPDATE goals SET status = 'completed', completed_at = NOW() WHERE user_id = :user_id AND status = 'active'");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->rowCount() > 0;
}

function goal_weight_record_count(int $userId, string $startDate): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM logs WHERE user_id = :user_id AND log_date >= :start_date');
    $stmt->execute([':user_id' => $userId, ':start_date' => $startDate]);
    return (int)$stmt->fetchColumn();
}

function calculate_goal_progress(float $start, float $current, float $target): float
{
    $distance = $target - $start;
    if (abs($distance) < 0.001) return 0.0;
    return max(0.0, min(100.0, (($current - $start) / $distance) * 100));
}

function calculate_required_weekly_pace(array $goal): float
{
    $start = new DateTimeImmutable($goal['start_date']);
    $target = new DateTimeImmutable($goal['target_date']);
    $days = max(1, (int)$start->diff($target)->days);
    return (((float)$goal['target_weight_kg'] - (float)$goal['start_weight_kg']) / $days) * 7;
}

function calculate_actual_weekly_pace(array $goal, float $current, int $recordCount): ?float
{
    $days = (int)(new DateTimeImmutable($goal['start_date']))->diff(goal_today())->days;
    if ($days < 7 || $recordCount < 3) return null;
    return (($current - (float)$goal['start_weight_kg']) / max(1, $days)) * 7;
}

function calculate_goal_status(float $required, ?float $actual): string
{
    if ($actual === null || abs($required) < 0.001) return 'データ収集中';
    if (($required < 0 && $actual > 0) || ($required > 0 && $actual < 0)) return '目標と反対方向に進んでいます';
    $ratio = abs($actual) / abs($required);
    if ($ratio >= 1.2) return '予定より速い';
    if ($ratio >= 0.8) return '順調';
    return 'やや遅れています';
}

function calculate_projected_goal_date(array $goal, float $current, ?float $actual): ?string
{
    $target = (float)$goal['target_weight_kg'];
    $direction = $target - (float)$goal['start_weight_kg'];
    if ($actual === null || abs($actual) < 0.01 || $actual * $direction <= 0) return null;
    $weeks = abs($target - $current) / abs($actual);
    return goal_today()->modify('+' . (int)ceil($weeks * 7) . ' days')->format('Y-m-d');
}

function validate_goal_input(string $weightRaw, string $dateRaw, ?float $currentWeight): array
{
    $errors = [];
    if (!preg_match('/^\d{1,3}(?:\.\d)?$/', $weightRaw) || !is_numeric($weightRaw) || (float)$weightRaw < 30 || (float)$weightRaw > 300) {
        $errors[] = '目標体重は30.0kg以上300.0kg以下、0.1kg単位で入力してください。';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateRaw, new DateTimeZone('Asia/Tokyo'));
    if (!$date || $date->format('Y-m-d') !== $dateRaw || $date <= goal_today()) {
        $errors[] = '目標日は今日より後の日付を入力してください。';
    }
    if ($currentWeight === null) {
        $errors[] = '目標を設定する前に、体重を1件以上記録してください。';
    } elseif (is_numeric($weightRaw) && abs((float)$weightRaw - $currentWeight) < 0.05) {
        $errors[] = '現在の平均体重と異なる目標体重を入力してください。';
    }
    return $errors;
}

function get_goal_metrics(int $userId, array $goal): array
{
    $current = get_recent_average_weight($userId);
    $start = (float)$goal['start_weight_kg'];
    $target = (float)$goal['target_weight_kg'];
    $recordCount = goal_weight_record_count($userId, (string)$goal['start_date']);
    $required = calculate_required_weekly_pace($goal);
    $actual = $current === null ? null : calculate_actual_weekly_pace($goal, $current, $recordCount);
    $remainingDays = (int)goal_today()->diff(new DateTimeImmutable($goal['target_date']))->format('%r%a');
    $reached = $current !== null && ($target < $start ? $current <= $target : $current >= $target);
    return [
        'current_weight' => $current,
        'remaining_weight' => $current === null ? null : abs($target - $current),
        'remaining_days' => $remainingDays,
        'progress' => $current === null ? 0.0 : calculate_goal_progress($start, $current, $target),
        'required_pace' => $required,
        'actual_pace' => $actual,
        'status' => calculate_goal_status($required, $actual),
        'projected_date' => $current === null ? null : calculate_projected_goal_date($goal, $current, $actual),
        'reached' => $reached,
    ];
}
