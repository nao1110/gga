# さくらインターネットへのデプロイ手順

## 事前準備

### 1. さくらインターネットの情報を確認
さくらインターネットのコントロールパネルにログインして、以下の情報を確認してください：

- **FTP情報**
  - FTPサーバー名: `xxx.sakura.ne.jp`
  - FTPアカウント名: `xxx`
  - FTPパスワード: `***`
  - 初期フォルダ: `/home/xxx/www/`

- **データベース情報**
  - データベースサーバー: `mysqlXXX.db.sakura.ne.jp`
  - データベース名: `xxx_careertre`
  - ユーザー名: `xxx`
  - パスワード: `***`

## デプロイ手順

### ステップ1: データベースの作成

1. さくらインターネットのコントロールパネルにログイン
2. 「データベースの設定」を開く
3. 「データベースの新規作成」をクリック
4. データベース名を入力（例: `careertre_db`）
5. 文字コードは `UTF-8` を選択
6. 作成完了後、接続情報をメモする

### ステップ2: データベース設定ファイルの更新

`lib/database.production.php` を編集：

```php
$host = 'mysqlXXX.db.sakura.ne.jp';  // ← さくらのDBサーバー名
$dbname = 'xxx_careertre';            // ← 作成したDB名
$username = 'xxx';                    // ← DBユーザー名
$password = 'your_password';          // ← DBパスワード
```

更新後、`lib/database.php` を `database.local.php` にリネームし、
`database.production.php` を `database.php` にリネーム

### ステップ3: ファイルのアップロード

#### 方法A: FTPクライアントを使用（推奨）

1. **FileZillaまたはCyberduckをインストール**
   - FileZilla: https://filezilla-project.org/
   - Cyberduck: https://cyberduck.io/

2. **FTP接続設定**
   - ホスト: `xxx.sakura.ne.jp`
   - ユーザー名: FTPアカウント名
   - パスワード: FTPパスワード
   - ポート: 21（通常のFTP）または 22（SFTP）

3. **ファイルをアップロード**
   - ローカルの `/Applications/XAMPP/xamppfiles/htdocs/gs_code/gga/` 配下の全ファイル
   - アップロード先: `/home/xxx/www/gga/` または `/home/xxx/www/`

#### 方法B: SSH + Git を使用（スタンダードプラン以上）

```bash
# さくらサーバーにSSH接続
ssh xxx@xxx.sakura.ne.jp

# wwwディレクトリに移動
cd www

# Gitリポジトリをクローン
git clone https://github.com/nao1110/gga.git

# ディレクトリに移動
cd gga

# 本番用設定に切り替え
mv lib/database.php lib/database.local.php
mv lib/database.production.php lib/database.php
```

### ステップ4: データベーステーブルの作成

1. **phpMyAdminにアクセス**
   - URL: `https://secure.sakura.ad.jp/rscontrol/`
   - ログイン後、「データベースの設定」→「管理ツールログイン」

2. **SQLファイルを実行**
   以下の順番でSQLファイルをインポート：
   
   ```
   database/setup.sql
   database/dummy_data.sql
   database/add_google_id_to_trainers.sql
   database/add_google_id_to_users.sql
   database/add_google_calendar_support.sql
   database/add_trainer_profile.sql
   database/alter_reserves_nullable.sql
   database/create_clients_table.sql
   database/create_client_reserves_table.sql
   database/add_test_client.sql
   database/update_trainer_profiles.sql
   database/update_persona_situations.sql
   database/add_persona_theme.sql
   ```

3. **recording_urlカラムの追加**
   ```sql
   ALTER TABLE reserves ADD COLUMN recording_url TEXT DEFAULT NULL AFTER meeting_url;
   ```

### ステップ5: ディレクトリパーミッションの設定

以下のディレクトリに書き込み権限を設定（FTPクライアントまたはSSHで）：

```
chmod 755 page/
chmod 755 controller/
chmod 755 assets/
```

### ステップ6: Google OAuth設定の更新

1. **Google Cloud Consoleにアクセス**
   - https://console.cloud.google.com/

2. **承認済みのリダイレクトURIを追加**
   - トレーナー用: `https://your-domain.sakura.ne.jp/gs_code/gga/controller/trainer/google_callback.php`
   - ユーザー用: `https://your-domain.sakura.ne.jp/gs_code/gga/controller/user/google_callback.php`

3. **承認済みのJavaScript生成元を追加**
   - `https://your-domain.sakura.ne.jp`

### ステップ7: 動作確認

1. **トップページにアクセス**
   - `https://your-domain.sakura.ne.jp/gs_code/gga/page/index.php`

2. **テストアカウントでログイン**
   - 管理者: `naoko.s1110@gmail.com`
   - トレーナー: `naoko.sato@smile-pj.com` / `naoko1110`
   - 受験者: `naoko@ouiinc.jp`

3. **各機能をテスト**
   - ユーザー登録
   - ログイン
   - 予約作成
   - Google Calendar連携
   - フィードバック投稿

## トラブルシューティング

### エラーログの確認

さくらインターネットのエラーログ：
- `/home/xxx/www/log/error_log`

PHPのエラー表示を一時的に有効化（デバッグ時のみ）：
```php
// ファイルの先頭に追加
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### よくある問題

1. **データベース接続エラー**
   - `lib/database.php` の接続情報を確認
   - データベースが作成されているか確認

2. **500エラー**
   - `.htaccess` の PHPバージョン指定を確認
   - ファイルのパーミッションを確認

3. **Google OAuth エラー**
   - リダイレクトURIが正しく設定されているか確認
   - クライアントシークレットファイルがアップロードされているか確認

## セキュリティ対策（本番運用前に必須）

1. **HTTPS化**
   - さくらインターネットの無料SSL証明書を有効化
   - `.htaccess` の HTTPS強制リダイレクトを有効化

2. **管理者パスワードの変更**
   - 本番環境用の強力なパスワードに変更

3. **エラー表示の無効化**
   - `php.ini` または `.htaccess` で `display_errors = Off`

4. **定期バックアップの設定**
   - さくらインターネットの自動バックアップを有効化
   - データベースのエクスポートを定期実行

## 更新手順（2回目以降）

```bash
# SSH接続
ssh xxx@xxx.sakura.ne.jp

# プロジェクトディレクトリに移動
cd www/gga

# 最新版を取得
git pull origin main

# 必要に応じてデータベースマイグレーション
# （新しいテーブルやカラムがある場合）
```

## サポート

問題が発生した場合：
1. エラーログを確認
2. データベース接続情報を再確認
3. さくらインターネットのサポートページを参照
