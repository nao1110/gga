# CareerTre - キャリトレ

キャリアコンサルタント試験受験者向けの実技練習サポートプラットフォーム

## 概要

CareerTreは、キャリアコンサルタント国家資格試験の受験者が、資格保有者（トレーナー）と1対1で実技面談の練習ができるWebアプリケーションです。

## 主な機能

### 受験者機能
- 新規登録・ログイン（通常 / Google OAuth）
- 5回の実技練習進捗管理
- 練習予約リクエスト（日時選択）
- 予約詳細・フィードバック確認
- 練習録画の視聴
- 自己フィードバック入力
- 5種類のペルソナ自動割り当て

### トレーナー（資格保有者）機能
- 新規登録・ログイン（通常 / Google OAuth）
- プロフィール管理（ニックネーム、経歴、対応可能時間）
- 予約承認機能
- オンライン面談URL管理
- フィードバックレポート入力（実技試験基準準拠）
- 完了セッション履歴管理

## 技術スタック

- **フロントエンド**: HTML5, CSS3, JavaScript
- **バックエンド**: PHP 8+
- **データベース**: MySQL 8.0+
- **CSSフレームワーク**: Pico.css v2
- **アイコン**: Lucide Icons
- **認証**: Google OAuth 2.0
- **依存関係管理**: Composer
- **開発環境**: XAMPP (Apache, MySQL)

## デザインシステム

- カラーパレット: パステルグラデーション (#ffeef8 → #e0f7fa → #e1f5e1)
- プライマリーカラー: Turquoise (#00BCD4)
- フォント: システムフォント（游ゴシック、Segoe UI）
- レスポンシブデザイン対応

## ディレクトリ構造

```
gga/
├── assets/
│   └── css/
│       ├── variables.css    # CSS変数定義
│       └── custom.css        # カスタムスタイル
├── controller/              # 🆕 処理制御（CRUDの表示以外）
│   ├── user/                # 受験者用コントローラー
│   └── trainer/             # トレーナー用コントローラー
├── lib/                     # 🆕 共通処理（DB接続など）
│   └── database.php         # データベース接続処理
├── database/                # データベース関連ファイル
│   ├── setup.sql            # テーブル作成SQL
│   ├── dummy_data.sql       # ダミーデータ投入SQL
│   ├── generate_password.php # パスワードハッシュ生成ツール
│   └── README.md            # DB設計ドキュメント
├── page/                    # 表示専用（View層）
│   ├── index.php            # トップページ
│   ├── user/                # 受験者向けページ
│   │   ├── register.php
│   │   ├── login.php
│   │   ├── mypage.php
│   │   └── mypage/
│   │       └── reserve/
│   │           ├── new.php
│   │           ├── detail.php
│   │           └── feedback/
│   │               └── detail.php
│   └── trainer/             # トレーナー向けページ
│       ├── register.php
│       ├── login.php
│       ├── mypage.php
│       └── mypage/
│           └── reserve/
│               └── feedback/
│                   └── input.php
└── README.md
```

### フォルダの役割

- **assets/**: 静的ファイル（CSS、画像など）
- **controller/**: ビジネスロジック・CRUD処理（Create/Update/Delete）
- **lib/**: 共通処理・ユーティリティ（DB接続、セッション管理、バリデーションなど）
- **database/**: データベース設計・セットアップファイル
- **page/**: 表示専用（View層）- 処理ロジックは含めない

## セットアップ

1. XAMPPをインストール
2. このリポジトリをクローン
```bash
git clone https://github.com/nao1110/gga.git
cd gga
```

3. XAMPPのhtdocsディレクトリに配置
```bash
# 例: /Applications/XAMPP/xamppfiles/htdocs/gs_code/gga
```

4. Apache起動

5. ブラウザでアクセス
```
http://localhost/gs_code/gga/page/index.php
```

6. データベースセットアップ
```bash
# MySQLにログイン
mysql -u root -p

# データベース作成とテーブルセットアップ
source database/setup.sql

# トレーナー用google_idカラム追加
source database/add_google_id_to_trainers.sql

# 受験者用google_idカラム追加
source database/add_google_id_to_users.sql
```

7. Composer依存関係のインストール
```bash
# プロジェクトディレクトリで実行
/Applications/XAMPP/xamppfiles/bin/php composer.phar install
```

8. Google OAuth設定（オプション）
   - `lib/google_auth.php.example` を `lib/google_auth.php` にコピー
   - Google Cloud Consoleでクライアント認証情報を取得
   - `lib/google_auth.php` に実際のクライアントIDとシークレットを設定
   - 詳細は `GOOGLE_OAUTH_SETUP.md` を参照

## 主要ページURL

### 受験者用
- トップ: `/page/index.php`
- 登録: `/page/user/register.php`
- ログイン: `/page/user/login.php`
- マイページ: `/page/user/mypage.php`
- 新規予約: `/page/user/mypage/reserve/new.php`
- 予約詳細: `/page/user/mypage/reserve/detail.php?id=1`

### トレーナー用
- 登録: `/page/trainer/register.php`
- ログイン: `/page/trainer/login.php`
- マイページ: `/page/trainer/mypage.php`
- フィードバック入力: `/page/trainer/mypage/reserve/feedback/input.php?id=1`

## 開発状況

- ✅ フロントエンド UI実装完了
- ✅ データベース設計完了（MySQL）
- ✅ MVC的フォルダ構造確立
- ✅ バックエンド処理（PHP）実装完了
- ✅ 認証システム実装完了（通常 + Google OAuth 2.0）
- ✅ フィードバックシステム実装完了（実技試験基準準拠）
- ✅ ペルソナ自動割り当てシステム実装完了
- ✅ トレーナープロフィール機能実装完了
- 🚧 動画アップロード機能実装予定
- 🚧 メール通知機能実装予定

## アーキテクチャ

### 設計方針
- **MVC的な分離**: View（page/）、Controller（controller/）、Model（lib/）
- **処理と表示の分離**: page/フォルダには表示ロジックのみ、処理はcontroller/へ
- **共通処理の集約**: DB接続やセッション管理はlib/に集約
- **セキュリティ**: パスワードハッシュ化、SQLインジェクション対策、XSS対策

### データフロー
```
1. ユーザーアクション (page/)
   ↓
2. コントローラー処理 (controller/)
   ↓
3. 共通処理・DB操作 (lib/)
   ↓
4. データ取得・保存
   ↓
5. 表示ページへリダイレクト (page/)
```

## 今後の予定

1. データベース設計（MySQL）
2. PHPバックエンド処理実装
3. セッション管理・認証実装
4. 動画アップロード機能
5. メール通知機能
6. 本番環境デプロイ

## ライセンス

© 2025 CareerTre - キャリトレ All rights reserved.

## 開発者

nao1110

## 更新履歴

- 2025-11-08: Google OAuth 2.0認証実装（トレーナー・受験者）
- 2025-11-08: トレーナープロフィール機能追加（全11名のデータ投入）
- 2025-11-07: ペルソナ詳細表示機能追加（家族構成含む）
- 2025-11-06: フィードバックシステム実装（実技試験基準準拠）
- 2025-11-05: ペルソナ自動割り当てシステム実装
- 2025-11-01: 初回コミット - UI完成、全画面実装完了
