<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$userId = require_login();

$stmt = db()->prepare("SELECT log_date, weight_kg, calories, steps, memo FROM logs WHERE user_id = :user_id ORDER BY log_date ASC");
$stmt->execute([':user_id' => $userId]);

$filename = 'body-calorie-log_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

fputcsv($output, ['日付', '体重kg', '摂取カロリーkcal', '歩数', 'メモ']);

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['log_date'],
        number_format((float)$row['weight_kg'], 1, '.', ''),
        $row['calories'] === null ? '' : (int)$row['calories'],
        $row['steps'] === null ? '' : (int)$row['steps'],
        (string)($row['memo'] ?? ''),
    ]);
}

fclose($output);
exit;
