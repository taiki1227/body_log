<?php
/**
 * ログイン後画面の共通ヘッダー。
 *
 * 各ページで以下を設定してから読み込みます。
 * $pageTitle       ページ見出し
 * $pageEyebrow     見出し上の短いラベル
 * $pageDescription 説明文
 * $pageActiveNav   records / progress / settings
 * $pageAppClass    main要素へ追加するクラス
 */
$pageTitle = isset($pageTitle) ? (string)$pageTitle : 'Body Log';
$pageEyebrow = isset($pageEyebrow) ? (string)$pageEyebrow : 'Body Log';
$pageDescription = isset($pageDescription) ? (string)$pageDescription : '';
$pageActiveNav = isset($pageActiveNav) ? (string)$pageActiveNav : '';
$pageAppClass = isset($pageAppClass) ? trim((string)$pageAppClass) : '';
$appName = isset($appName) ? (string)$appName : 'Body Log';
$username = isset($username) ? (string)$username : '';
$documentTitle = $pageTitle . ' | ' . $appName;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($documentTitle) ?></title>
  <link rel="stylesheet" href="style.css?v=20260722-goal-progress">
</head>
<body>
  <main class="app<?= $pageAppClass !== '' ? ' ' . h($pageAppClass) : '' ?>">
    <header class="app-header">
      <div class="app-header-copy">
        <p class="eyebrow"><?= h($pageEyebrow) ?></p>
        <h1><?= h($pageTitle) ?></h1>
        <?php if ($pageDescription !== ''): ?>
          <p class="description"><?= h($pageDescription) ?></p>
        <?php endif; ?>
      </div>

      <div class="header-actions">
        <?php if ($username !== ''): ?>
          <span class="username" title="<?= h($username) ?>"><?= h($username) ?></span>
        <?php endif; ?>

        <a class="secondary-link" href="./">Body Logとは</a>

        <a
          class="secondary-link<?= $pageActiveNav === 'records' ? ' is-active' : '' ?>"
          href="records"
          <?= $pageActiveNav === 'records' ? 'aria-current="page"' : '' ?>
        >記録</a>

        <a
          class="secondary-link<?= $pageActiveNav === 'progress' ? ' is-active' : '' ?>"
          href="progress"
          <?= $pageActiveNav === 'progress' ? 'aria-current="page"' : '' ?>
        >進捗</a>

        <a
          class="secondary-link<?= $pageActiveNav === 'settings' ? ' is-active' : '' ?>"
          href="settings"
          <?= $pageActiveNav === 'settings' ? 'aria-current="page"' : '' ?>
        >アカウント設定</a>

        <a class="secondary-link" href="export">CSV出力</a>
        <a class="secondary-link logout-link" href="logout">ログアウト</a>
      </div>
    </header>
