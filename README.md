# CareerTre - キャリトレ

キャリアコンサルタント試験受験者向けの実技練習サポートプラットフォーム

## 概要

CareerTreは、キャリアコンサルタント国家資格試験の受験者が、資格保有者（トレーナー）と1対1で実技面談の練習ができるWebアプリケーションです。

## 主な機能

### 受験者機能
- 新規登録・ログイン
- 5回の実技練習進捗管理
- 練習予約リクエスト（日時選択）
- 予約詳細・フィードバック確認
- 練習録画の視聴
- 自己フィードバック入力

### トレーナー（資格保有者）機能
- 新規登録・ログイン
- 予約承認機能
- オンライン面談URL管理
- フィードバックレポート入力
- 完了セッション履歴管理

## 技術スタック

- **フロントエンド**: HTML5, CSS3, JavaScript
- **バックエンド**: PHP 8+
- **CSSフレームワーク**: Pico.css v2
- **アイコン**: Lucide Icons
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
- ✅ ダミーデータでの動作確認済み
- ✅ データベース設計完了（MySQL）
- ✅ MVC的フォルダ構造確立
- 🚧 バックエンド処理（PHP）実装中
- 🚧 認証システム実装予定

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

- 2025-11-01: 初回コミット - UI完成、全画面実装完了
