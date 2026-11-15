##　アプリケーション名
COACHTECH 勤怠管理アプリ

##　概要
本アプリは勤怠情報を登録・修正・管理するアプリです。
一般ユーザーと管理者ユーザーに対応しており、一般ユーザーは出勤・休憩・退勤の打刻、勤怠の修正申請、勤怠情報の閲覧を行うことができます。管理者は一般ユーザーの情報の閲覧、勤怠の修正、修正申請の承認、勤怠のCSV出力を行うことができます。

## 環境構築手順
1. Laravel 10 プロジェクトを新規作成（最新版を避けるためバージョンを明示）
```
以下のDockerコマンドを実行して、Laravel 10.xを明示的に指定してプロジェクトを作成します。

docker run --rm \
  -u ""$(id -u):$(id -g)"" \
  -v ""$(pwd):/var/www/html"" -w /var/www/html \
  -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
  laravelsail/php82-composer:latest \
  composer create-project laravel/laravel:^10.0 attendance-app
cd attendance-app
```
2. Laravel Sail を導入（mysql, mailpit）
```
"docker run --rm -u ""$(id -u):$(id -g)"" -v ""$(pwd):/var/www/html"" -w /var/www/html \
  laravelsail/php82-composer:latest \
  php artisan sail:install --with=mysql,mailpit

Apple Silicon(M1/M2/M3/M4等)の場合は compose.yaml の mysql に「platform: 'linux/amd64'」を追記。"
```
3. phpMyAdmin を compose.yaml に追記（mysql の下に追加）
```
compose.yaml を開き、mysql サービスの後に以下の設定を追加してください。

phpmyadmin:
  image: 'phpmyadmin:latest'
  ports:
    - '${FORWARD_PHPMYADMIN_PORT:-8080}:80'
  environment:
    PMA_HOST: mysql
    PMA_USER: '${DB_USERNAME}'
    PMA_PASSWORD: '${DB_PASSWORD}'
  networks:
    - sail
  depends_on:
    - mysql
```
4. .env を編集（DB / メール）
```
.env ファイルを開き、データベース接続情報が以下と一致していることを確認します。

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```
5. 提供Bladeの移入（以下の「提供Bladeについて」を参照）
```
"git clone -b basic https://github.com/coachtech-prepared-file/Preparedblade-mockcase-Attendance.git
# → クローンした resources/ を、自分のプロジェクトの resources/ に上書きコピー
# （応用機能の着手時に advanced ブランチの resources/ を再移入）"
```
6. フロントエンド（Vite）
```
vite.config.js を以下の内容に置き換える（提供CSSを個別に入力指定する）

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/app.js',
        'resources/css/sanitize.css',
        'resources/css/common.css',
        'resources/css/auth/verify-email.css',
        'resources/css/user/register.css',
        'resources/css/user/user-login.css',
        'resources/css/user/attendance-register.css',
        'resources/css/user/user-attendance-list.css',
        'resources/css/user/user-detail.css',
        'resources/css/user/user-application-list.css',
        'resources/css/admin/admin-login.css',
        'resources/css/admin/admin-attendance-list.css',
        'resources/css/admin/admin-detail.css',
        'resources/css/admin/admin-application-list.css',
        'resources/css/admin/admin-application-detail.css',
        'resources/css/admin/staff-list.css',
        'resources/css/admin/staff-attendance-list.css',
        'resources/css/reports/index.css', // ※ 応用（マイ勤怠レポート）用。基本のみの場合はこの行を除いてよい。
      ],
      refresh: true,
    }),
  ],
});"
```
7. アプリ起動 & 初期化
```
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
（エイリアス推奨: alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'）

■ 指定技術スタック外のパッケージは追加しないこと（laravel-lang/* 等）。
  日本語化は lang/ へメッセージファイルを手動配置して対応する（サプライチェーン攻撃対策）
```
## 初期データ
### 1. ユーザー1（一般ユーザー）
```
メールアドレス: user1@example.com
パスワード: password
```
### 2. ユーザー2（一般ユーザー）
```
メールアドレス: user2@example.com
パスワード: password
```
### 3. ユーザー3（管理者）
```
メールアドレス: user3@example.com
パスワード: password
```

## 使用技術(実行環境)
- PHP 8.2 / Laravel 10.x
- MySQL 8.0
- Nginx
- Docker, Laravel Sail, phpMyAdmin
- Mailpit
- Node.js 18.19
- npm 9.2
- PHPUnit

## ER図
<img src="./public/images/attendance-ER.png" width="500">

## APIエンドポイント一覧
| メソッド | エンドポイント | 説明 |
| :--- | :--- | :--- |
| `GET` | `/api/v1/attendance-records` | 勤怠一覧取得 |
| `GET` | `/api/v1/attendance-records/{attendanceRecord}` | 勤怠詳細取得 |
| `POST` | `/api/v1/attendance-records` | 勤怠新規作成 |
| `PUT` | `/api/v1/attendance-records/{attendanceRecord}` | 勤怠更新 |
| `DELETE` | `/api/v1/attendance-records/{attendanceRecord}` | 勤怠削除 |

## テスト
### 実行コマンド
```
./vendor/bin/sail php artisan test
```

### 実行結果
```
63 passed
186 assertions
```

### 実行内容
```
認証機能
ログイン認証機能
日時取得機能
ステータス確認機能
勤怠登録機能
勤怠一覧情報取得機能
勤怠詳細情報取得機能
勤怠詳細情報修正機能
勤怠情報修正申請承認機能
```

## URL
- 開発環境:http://localhost
- phpMyAdmin:http://localhost:8080
- Mailpit:http://localhost:8025

- 一般ユーザーログイン画面:http://localhost/login
- 管理者ログイン画面:http://localhost/admin/login

## 作成者
小路　真輝
