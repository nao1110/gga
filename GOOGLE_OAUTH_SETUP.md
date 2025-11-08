# Google OAuth 2.0 認証設定手順

## 概要
キャリアコンサルタント有資格者は、Googleアカウントを使用してCareerTreにログインできます。

## セットアップ手順

### 1. データベースマイグレーション

**PHPMyAdmin**または**MySQLコマンドライン**で以下のSQLを実行してください：

```sql
USE careertre_db;

ALTER TABLE trainers 
ADD COLUMN google_id VARCHAR(255) NULL UNIQUE 
COMMENT 'Google OAuth認証のユーザーID' 
AFTER email;
```

または、用意されているSQLファイルを実行：
```bash
# database/add_google_id_to_trainers.sql を実行
```

### 2. Google Cloud Console設定

#### 2.1 プロジェクト作成
1. [Google Cloud Console](https://console.cloud.google.com/)にアクセス
2. 新しいプロジェクトを作成（例: "CareerTre-OAuth"）

#### 2.2 OAuth同意画面の設定
1. 「APIとサービス」→「OAuth同意画面」を選択
2. ユーザータイプ: **外部**を選択
3. アプリ情報を入力:
   - アプリ名: CareerTre
   - ユーザーサポートメール: あなたのメールアドレス
   - デベロッパーの連絡先情報: あなたのメールアドレス
4. スコープ: `email`と`profile`を追加
5. テストユーザー: 必要に応じて追加（開発環境では不要）

#### 2.3 OAuth 2.0 クライアントIDの作成
1. 「APIとサービス」→「認証情報」を選択
2. 「認証情報を作成」→「OAuth クライアント ID」をクリック
3. アプリケーションの種類: **ウェブアプリケーション**を選択
4. 名前: CareerTre Trainer Login
5. **承認済みのリダイレクト URI**に以下を追加:
   ```
   http://localhost/gs_code/gga/controller/trainer/google_callback.php
   ```
   
   **本番環境の場合:**
   ```
   https://yourdomain.com/controller/trainer/google_callback.php
   ```

6. 「作成」をクリック
7. **クライアントID**と**クライアントシークレット**をメモ

### 3. アプリケーション設定

`lib/google_auth.php`ファイルを開き、以下の箇所を更新してください：

```php
// Google Cloud Consoleで取得したクライアントIDとシークレット
$client->setClientId('YOUR_CLIENT_ID.apps.googleusercontent.com');
$client->setClientSecret('YOUR_CLIENT_SECRET');
```

**実際の値に置き換える:**
```php
$client->setClientId('123456789-abcdefghijklmnop.apps.googleusercontent.com');
$client->setClientSecret('GOCSPX-abc123xyz789');
```

### 4. 動作確認

#### 4.1 ログインフロー確認
1. ブラウザで `http://localhost/gs_code/gga/page/index.php` にアクセス
2. 「キャリアコンサルタント有資格者」カードの「Googleでログイン」ボタンをクリック
3. Google認証画面が表示される
4. Googleアカウントでログイン
5. アプリへのアクセス許可を承認
6. `trainer/mypage.php`にリダイレクトされる

#### 4.2 データベース確認
PHPMyAdminで`trainers`テーブルを確認:
- 新しいトレーナーレコードが作成されているか
- `google_id`カラムにGoogle User IDが保存されているか
- `email`にGoogleアカウントのメールアドレスが保存されているか

### 5. トラブルシューティング

#### エラー: "redirect_uri_mismatch"
**原因:** Google Cloud Consoleの承認済みリダイレクトURIが一致していない

**解決方法:**
1. Google Cloud Console → 認証情報 → OAuth 2.0 クライアントIDを開く
2. 承認済みのリダイレクトURIに以下が正確に登録されているか確認:
   ```
   http://localhost/gs_code/gga/controller/trainer/google_callback.php
   ```
3. URLの末尾にスラッシュがないことを確認

#### エラー: "Invalid Client"
**原因:** クライアントIDまたはシークレットが間違っている

**解決方法:**
1. Google Cloud Consoleで正しいクライアントIDとシークレットを確認
2. `lib/google_auth.php`の値を正確にコピー＆ペースト

#### エラー: "access_denied"
**原因:** ユーザーがGoogleの同意画面でアクセスを拒否した

**解決方法:**
- 再度ログインを試み、同意画面で「許可」をクリック

#### データベースエラー: "Duplicate entry for key 'google_id'"
**原因:** 同じGoogleアカウントで既にトレーナーレコードが存在する

**解決方法:**
- これは正常な動作です。`google_callback.php`は既存のレコードを更新します

## セキュリティ上の注意

### 本番環境への移行時
1. **HTTPS必須**: 本番環境では必ずHTTPSを使用
2. **リダイレクトURI更新**: Google Cloud Consoleで本番環境のURLを登録
3. **クライアントシークレット保護**: 
   - 環境変数やシークレット管理サービスを使用
   - Gitにコミットしない
4. **セッションセキュリティ**: 
   - `session.cookie_secure = On`
   - `session.cookie_httponly = On`
   - `session.cookie_samesite = Strict`

### 環境変数の使用（推奨）
`lib/google_auth.php`で環境変数を使用:

```php
$client->setClientId(getenv('GOOGLE_CLIENT_ID'));
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
```

`.env`ファイル（Gitignore必須）:
```
GOOGLE_CLIENT_ID=123456789-abcdefghijklmnop.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abc123xyz789
```

## ファイル構成

```
controller/trainer/
  ├── google_login.php       # OAuth認証開始
  └── google_callback.php    # OAuth認証コールバック処理

lib/
  └── google_auth.php        # Google Client設定

database/
  └── add_google_id_to_trainers.sql  # google_idカラム追加SQL

page/
  └── index.php             # Googleログインボタン追加
```

## 参考リンク
- [Google Identity Platform - OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Google API Client Library for PHP](https://github.com/googleapis/google-api-php-client)
- [Google Cloud Console](https://console.cloud.google.com/)
