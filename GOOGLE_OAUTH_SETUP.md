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

---

# Google Calendar API統合

## 概要
トレーナーがクライアントの予約を承認すると、自動的に以下が実行されます：
- トレーナーとクライアント両方のGoogleカレンダーにイベントが作成される
- Google Meetが自動生成され、URLが予約に紐付けられる
- 両方のマイページにMeet URLが表示される

## 追加セットアップ手順

### 1. Google Calendar API の有効化

1. [Google Cloud Console](https://console.cloud.google.com/)にアクセス
2. 既存のプロジェクト（CareerTre-OAuth）を選択
3. 「APIとサービス」→「ライブラリ」を開く
4. 「Google Calendar API」を検索
5. **「有効にする」**をクリック

### 2. OAuth スコープの追加

OAuth同意画面でスコープを追加:
1. 「APIとサービス」→「OAuth同意画面」を開く
2. 「アプリを編集」をクリック
3. 「スコープを追加または削除」セクションで以下を追加:
   - `https://www.googleapis.com/auth/calendar.events`（カレンダーイベントの作成・編集権限）
4. 保存して続行

### 3. データベースマイグレーション

以下のSQLを実行してアクセストークンとMeet URLを保存:

```sql
USE careertre_db;

-- trainersテーブルにアクセストークンカラムを追加
ALTER TABLE trainers 
ADD COLUMN google_access_token TEXT NULL 
COMMENT 'Google OAuth アクセストークン（JSON形式）' 
AFTER google_id;

-- usersテーブルにアクセストークンカラムを追加
ALTER TABLE users 
ADD COLUMN google_access_token TEXT NULL 
COMMENT 'Google OAuth アクセストークン（JSON形式）' 
AFTER google_id;

-- client_reservesテーブルにGoogle Meet URLカラムを追加
ALTER TABLE client_reserves 
ADD COLUMN meet_url VARCHAR(512) NULL 
COMMENT 'Google Meet URL' 
AFTER status;
```

または、用意されているSQLファイルを実行:
```bash
mysql -u root careertre_db < database/add_google_calendar_support.sql
```

### 4. コードの更新

#### 4.1 google_auth.php
`lib/google_auth.php`に既にCalendar APIスコープが追加されています：

```php
// Calendar API スコープの追加
$client->addScope(Google\Service\Calendar::CALENDAR_EVENTS);
```

#### 4.2 OAuth コールバック
トレーナーとユーザーのOAuthコールバックで、アクセストークンがデータベースに保存されるようになっています。

### 5. 動作フロー

1. **ユーザー/トレーナーがGoogleでログイン**
   - アクセストークン（Calendar API権限付き）がデータベースに保存される

2. **クライアントが予約リクエストを作成**
   - `client_reserves`テーブルに予約が登録される（status: 'pending'）

3. **トレーナーが予約を承認**
   - `controller/trainer/reserve_approve.php`が実行される
   - 両者のアクセストークンを取得
   - トークンが期限切れの場合、自動更新
   - Calendar APIでイベントを作成（conferenceDataでMeet URLを生成）
   - トレーナーとクライアント両方のカレンダーに追加
   - Meet URLを`client_reserves.meet_url`に保存
   - ステータスを'confirmed'に更新

4. **Meet URLの表示**
   - トレーナーマイページ: `page/trainer/mypage.php`
   - クライアントマイページ: `page/client/mypage.php`
   - 両方で「Google Meetに参加」ボタンが表示される

### 6. Google Calendar API の機能

#### イベントの構造
```php
$event = new Google\Service\Calendar\Event([
    'summary' => 'キャリア相談 - クライアント名 & トレーナー名',
    'description' => '相談内容の詳細',
    'start' => ['dateTime' => '2025-01-20T14:00:00+09:00', 'timeZone' => 'Asia/Tokyo'],
    'end' => ['dateTime' => '2025-01-20T15:00:00+09:00', 'timeZone' => 'Asia/Tokyo'],
    'attendees' => [
        ['email' => 'trainer@example.com'],
        ['email' => 'client@example.com']
    ],
    'conferenceData' => [
        'createRequest' => [
            'requestId' => 'unique_id',
            'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
        ]
    ]
]);
```

#### Meet URL の取得
```php
$createdEvent = $calendarService->events->insert('primary', $event, [
    'conferenceDataVersion' => 1,  // Meet生成に必須
    'sendUpdates' => 'all'          // 全参加者に通知メール送信
]);

$meet_url = $createdEvent->getHangoutLink(); // Meet URLを取得
```

### 7. トラブルシューティング

#### エラー: "Calendar API has not been used"
**原因:** Google Calendar APIが有効化されていない

**解決方法:**
1. Google Cloud Console → APIとサービス → ライブラリ
2. Google Calendar APIを検索して有効化

#### エラー: "Insufficient Permission"
**原因:** OAuth同意画面でカレンダースコープが追加されていない

**解決方法:**
1. OAuth同意画面で`https://www.googleapis.com/auth/calendar.events`スコープを追加
2. ユーザーに再度Googleでログインしてもらう（新しいスコープで再認証）

#### エラー: "Token has been expired or revoked"
**原因:** アクセストークンの有効期限切れ

**解決方法:**
- コード内で自動的にリフレッシュトークンを使用して更新される
- 更新に失敗した場合は、ユーザーに再ログインを促す

#### Meet URLが生成されない
**原因:** `conferenceDataVersion`パラメータが不足

**解決方法:**
```php
$service->events->insert('primary', $event, [
    'conferenceDataVersion' => 1  // この指定が必須
]);
```

#### APIクォータ超過
**原因:** 1日あたりのAPI呼び出し制限に到達

**解決方法:**
1. Google Cloud Console → APIとサービス → クォータ
2. Calendar APIのクォータを確認
3. 必要に応じてクォータ引き上げをリクエスト

### 8. API使用制限

Google Calendar APIの無料枠：
- **1ユーザーあたり100秒ごとに1,000,000クエリ**
- **1日あたり100,000,000クエリ**（プロジェクト全体）

通常の使用では十分ですが、大量の予約がある場合は注意してください。

### 9. セキュリティ考慮事項

1. **アクセストークンの保護**
   - データベースのTEXT型で暗号化なしで保存（Googleが推奨）
   - データベース自体のセキュリティを強化

2. **リフレッシュトークン**
   - 初回認証時にのみ取得可能
   - 保存して長期的なアクセスに使用

3. **トークンの失効**
   - ユーザーがGoogleアカウント設定でアプリのアクセスを取り消した場合
   - エラーハンドリングで再ログインを促す

### 10. 関連ファイル

```
controller/trainer/
  └── reserve_approve.php        # 予約承認＋Calendar API統合

lib/
  └── google_auth.php            # Calendar APIスコープ追加済み

page/trainer/
  └── mypage.php                # Meet URL表示（トレーナー側）

page/client/
  └── mypage.php                # Meet URL表示（クライアント側）

database/
  └── add_google_calendar_support.sql  # アクセストークン＋Meet URLカラム追加
```

### 11. 参考リンク
- [Google Calendar API - PHP クイックスタート](https://developers.google.com/calendar/api/quickstart/php)
- [Google Calendar API - Events: insert](https://developers.google.com/calendar/api/v3/reference/events/insert)
- [Conference Data - Meet生成](https://developers.google.com/calendar/api/guides/conference)
- [OAuth 2.0 Scopes for Google APIs](https://developers.google.com/identity/protocols/oauth2/scopes#calendar)
