# career-trainers.com デプロイ手順

## 環境情報

### ドメイン
- **本番URL**: https://career-trainers.com
- **DNS管理**: Cloudflare

### さくらインターネット
- **データベースサーバー**: mysql80.gsacademy.sakura.ne.jp
- **データベース名**: gsacademy_careertrainers
- **FTPサーバー**: gsacademy.sakura.ne.jp（推定）

---

## デプロイ手順

### ステップ1: FileZillaでFTP接続

1. **FileZillaを起動**

2. **接続情報を入力**
   - ホスト: `gsacademy.sakura.ne.jp`
   - ユーザー名: `gsacademy`
   - パスワード: （FTPパスワードを入力）
   - ポート: `21`

3. **「クイック接続」をクリック**

   ✅ 接続成功すると、右側にサーバーのファイル一覧が表示されます

### ステップ2: ファイルをアップロード

1. **左側（ローカル）**: `/Applications/XAMPP/xamppfiles/htdocs/gs_code/gga/` を開く

2. **右側（リモート）**: `/home/gsacademy/www/` に移動

3. **以下のフォルダ・ファイルを全てアップロード**
   ```
   ✅ assets/
   ✅ controller/
   ✅ database/
   ✅ lib/
   ✅ page/
   ✅ vendor/
   ✅ .htaccess
   ✅ composer.json
   ✅ README.md
   ✅ その他全てのPHPファイル
   ```

4. **アップロード中の注意**
   - `vendor/` フォルダは容量が大きいので時間がかかります（数分程度）
   - エラーが出た場合は再試行してください

### ステップ3: 本番環境用の設定に切り替え

FileZilla上で以下の操作を実行：

1. **`lib/database.php` を右クリック → 名前変更**
   - 新しい名前: `database.local.php`

2. **`lib/database.production.php` を右クリック → 名前変更**
   - 新しい名前: `database.php`

### ステップ4: データベーステーブルの作成

1. **phpMyAdminにアクセス**
   - URL: https://secure.sakura.ad.jp/rscontrol/
   - さくらインターネットのコントロールパネルにログイン
   - 「データベースの設定」→「管理ツールログイン」

2. **左側でデータベースを選択**
   - `gsacademy_careertrainers` をクリック

3. **「SQL」タブをクリック**

4. **以下のSQLファイルを順番にインポート**

   ① **setup.sql**（基本テーブル作成）
   ② **dummy_data.sql**（初期データ）
   ③ **add_google_id_to_trainers.sql**
   ④ **add_google_id_to_users.sql**
   ⑤ **add_google_calendar_support.sql**
   ⑥ **add_trainer_profile.sql**
   ⑦ **alter_reserves_nullable.sql**
   ⑧ **create_clients_table.sql**
   ⑨ **create_client_reserves_table.sql**
   ⑩ **add_test_client.sql**
   ⑪ **update_trainer_profiles.sql**
   ⑫ **update_persona_situations.sql**
   ⑬ **add_persona_theme.sql**

5. **最後に以下のSQLを実行**
   ```sql
   ALTER TABLE reserves ADD COLUMN recording_url TEXT DEFAULT NULL AFTER meeting_url;
   ```

### ステップ5: Google OAuth設定の更新

1. **Google Cloud Consoleにアクセス**
   - https://console.cloud.google.com/

2. **認証情報ページを開く**
   - プロジェクト選択 → 「APIとサービス」→「認証情報」

3. **OAuth 2.0 クライアントIDを編集**

4. **承認済みのリダイレクトURIを追加**
   ```
   https://career-trainers.com/controller/trainer/google_callback.php
   https://career-trainers.com/controller/user/google_callback.php
   ```

5. **承認済みのJavaScript生成元を追加**
   ```
   https://career-trainers.com
   ```

6. **保存**

### ステップ6: Cloudflare DNS設定

1. **Cloudflareダッシュボードにログイン**
   - https://dash.cloudflare.com/

2. **career-trainers.com を選択**

3. **DNS設定を確認**
   - タイプ: `A` または `CNAME`
   - 名前: `@` または `career-trainers.com`
   - 値: さくらインターネットのIPアドレス（さくらのコントロールパネルで確認）
   - プロキシ状態: オレンジクラウド（プロキシあり）推奨

4. **SSL/TLS設定**
   - 「SSL/TLS」→「概要」
   - 暗号化モード: 「フル」または「フル（厳密）」を選択

### ステップ7: 動作確認

1. **トップページにアクセス**
   ```
   https://career-trainers.com/page/index.php
   ```

2. **テストアカウントでログイン**
   - 管理者: naoko.s1110@gmail.com（Googleログイン）
   - トレーナー: naoko.sato@smile-pj.com / naoko1110
   - 受験者: naoko@ouiinc.jp（Googleログイン）

3. **各機能をテスト**
   - ✅ ユーザー登録
   - ✅ ログイン
   - ✅ 予約作成
   - ✅ 予約承認
   - ✅ フィードバック入力
   - ✅ 録画URL管理（管理者）

---

## トラブルシューティング

### 1. データベース接続エラー
```
SQLSTATE[HY000] [1045] Access denied for user...
```
**対処法**: 
- `lib/database.php` の接続情報を確認
- phpMyAdminで接続できるか確認

### 2. 500 Internal Server Error
**対処法**:
- `.htaccess` のPHPバージョン指定を確認
- エラーログを確認: `/home/gsacademy/www/log/error_log`

### 3. ページが表示されない（404エラー）
**対処法**:
- ファイルが正しくアップロードされているか確認
- パスを確認: `https://career-trainers.com/page/index.php`

### 4. Google OAuth エラー
```
Error 400: redirect_uri_mismatch
```
**対処法**:
- Google Cloud ConsoleのリダイレクトURIが正しいか確認
- HTTPSでアクセスしているか確認

---

## セキュリティチェックリスト

本番運用前に必ず確認：

- [ ] HTTPS化完了（CloudflareのSSL有効）
- [ ] データベースパスワードが強力か確認
- [ ] 管理者アカウントのパスワード変更
- [ ] PHPのエラー表示を無効化（本番環境）
- [ ] 不要なファイルを削除（test_password.phpなど）
- [ ] .gitignoreの設定確認
- [ ] Google OAuthの本番環境設定完了
- [ ] 定期バックアップの設定

---

## 更新手順（2回目以降）

```bash
# ローカルでGitから最新版を取得
cd /Applications/XAMPP/xamppfiles/htdocs/gs_code/gga
git pull origin main

# FileZillaで変更されたファイルのみ再アップロード
# または全ファイルを上書きアップロード
```

---

## サポート連絡先

- さくらインターネットサポート: https://help.sakura.ad.jp/
- Cloudflareサポート: https://support.cloudflare.com/
