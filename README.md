# Body Log

体重・摂取カロリー・歩数を記録し、減量や体重管理の進み方を確認するためのWebアプリです。

毎日の数値だけでなく、7日平均体重、前週比、体重トレンド、推定基礎代謝、実績ベースの推定TDEEなどを表示します。

## 公開URL

- トップページ: `https://tkgstudio.com/body-log/`
- 記録画面: `https://tkgstudio.com/body-log/records`
- 推移グラフ: `https://tkgstudio.com/body-log/progress`

## 主な機能

- ユーザー登録・ログイン・ログアウト
- 体重、摂取カロリー、歩数、メモの記録
- 同日の記録更新・削除
- 7日平均体重と前週比の表示
- 体重トレンドの表示
- 推定基礎代謝の計算
- 体重と摂取カロリーの実績から推定TDEEを算出
- 体重・カロリー・歩数のグラフ表示
- CSVエクスポート
- アカウント情報と身体情報の変更
- メールによるパスワード再設定
- Cloudflare Turnstile対応
- 利用規約・プライバシーポリシー

## 使用技術

- PHP 7.4+
- MariaDB / MySQL
- HTML
- CSS
- JavaScript
- Apache `mod_rewrite`
- Cloudflare Turnstile（任意）

フレームワークは使用せず、PHPで構築しています。

## ディレクトリ構成

```text
body-log/
├─ app/
│  ├─ bootstrap.php
│  ├─ error.php
│  ├─ password_reset.php
│  └─ .htaccess
├─ account_settings.php
├─ dashboard.php
├─ export_csv.php
├─ forgot_password.php
├─ index.php
├─ landing.php
├─ login.php
├─ logout.php
├─ privacy.php
├─ register.php
├─ reset_password.php
├─ terms.php
├─ turnstile.php
├─ style.css
├─ landing.css
├─ legal.css
└─ .htaccess
```

アプリ共通処理は、主に`app/bootstrap.php`にまとめています。

## 設定ファイル

DB接続情報やメール設定などの秘密情報は、公開ディレクトリには置きません。

本番環境では、以下に配置します。

```text
/home/ユーザー名/domains/ドメイン/private/body-log/config.php
```

現在の本番環境では次の場所です。

```text
/home/taiki1227/domains/tkgstudio.com/private/body-log/config.php
```

`app/bootstrap.php`が、この非公開設定ファイルを読み込みます。

設定例:

```php
<?php

return [
    'app_name' => 'Body Log',
    'app_url' => 'https://example.com/body-log',

    'db_host' => 'localhost',
    'db_name' => 'database_name',
    'db_user' => 'database_user',
    'db_pass' => 'database_password',

    'session_name' => 'body_log_session',
    'session_cookie_path' => '/body-log/',

    'allow_registration' => false,
    'registration_code' => '',

    'mail_from' => 'no-reply@example.com',
    'mail_from_name' => 'Body Log',
    'support_email' => 'support@example.com',

    'operator_name' => '運営者名',
    'operator_address' => '',

    'smtp' => [
        'enabled' => false,
        'host' => '',
        'port' => 587,
        'secure' => 'tls',
        'username' => '',
        'password' => '',
    ],

    'turnstile' => [
        'enabled' => false,
        'site_key' => '',
        'secret_key' => '',
        'hostname' => '',
    ],
];
```

実際のパスワード、SMTP認証情報、Turnstileの秘密鍵は、GitHubへコミットしないでください。

## セットアップ

1. MariaDBまたはMySQLに空のデータベースとユーザーを作成します。
2. アプリ一式をWeb公開ディレクトリへ配置します。
3. 公開ディレクトリ外に`private/body-log/config.php`を作成します。
4. 設定ファイルへDB接続情報を入力します。
5. Apacheで`mod_rewrite`と`.htaccess`を有効にします。
6. ブラウザでトップページまたは登録ページを開きます。

初回アクセス時に、必要なテーブルと不足カラムをアプリ側で作成・更新します。

そのため、DBユーザーには少なくとも以下の権限が必要です。

- `SELECT`
- `INSERT`
- `UPDATE`
- `DELETE`
- `CREATE`
- `ALTER`
- `INDEX`

## URL

| 画面 | URL |
|---|---|
| トップ | `/body-log/` |
| 記録 | `/body-log/records` |
| 推移グラフ | `/body-log/progress` |
| アカウント設定 | `/body-log/settings` |
| ログイン | `/body-log/login` |
| ユーザー登録 | `/body-log/signup` |
| ログアウト | `/body-log/logout` |
| CSV出力 | `/body-log/export` |
| パスワード再設定申請 | `/body-log/forgot-password` |
| 新しいパスワード設定 | `/body-log/reset-password` |
| プライバシーポリシー | `/body-log/privacy` |
| 利用規約 | `/body-log/terms` |

`.htaccess`で、公開URLを各PHPファイルへ内部的に振り分けています。

## データベース

主に次の2テーブルを使用します。

### `users`

ユーザー情報、ログイン用パスワードハッシュ、身体情報、パスワード再設定トークンを保存します。

### `logs`

ユーザーごとの日次記録を保存します。

- 記録日
- 体重
- 摂取カロリー
- 歩数
- メモ

同じユーザーが同じ日付の記録を複数作らないよう、ユーザーIDと日付にユニーク制約を設定しています。

## セキュリティ

- DB接続情報を公開ディレクトリ外で管理
- パスワードを`password_hash()`でハッシュ化
- PDOプリペアドステートメントを使用
- CSRFトークンを使用
- セッションCookieに`HttpOnly`と`SameSite=Lax`を設定
- HTML出力をエスケープ
- パスワード再設定トークンをハッシュ化してDBへ保存
- `app`ディレクトリへの直接アクセスを`.htaccess`で拒否
- Cloudflare Turnstileを任意で利用可能

## 推定TDEEについて

推定TDEEは、直近の体重と摂取カロリーの記録から算出する参考値です。

日々の体重は水分量や食事内容によって変動するため、単日の増減ではなく、7日平均や一定期間の体重トレンドと合わせて確認する前提です。

本アプリが表示する数値は医療上の診断ではありません。

## バックアップ

更新前には、次の両方をバックアップしてください。

- `public_html/body-log`
- MariaDB / MySQLのデータベース

秘密設定ファイルも別途安全な場所へ保管してください。

```text
private/body-log/config.php
```

## 開発メモ

PHPファイルを変更した場合は、アップロード前に構文チェックを行います。

```bash
php -l ファイル名.php
```

フォルダ内をまとめて確認する例:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

## ライセンス

個人開発プロジェクトです。  
再利用・再配布については、リポジトリ管理者へ確認してください。
