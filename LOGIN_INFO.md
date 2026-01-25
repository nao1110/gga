# 🔐 CareerTre (キャリアトレーナーズ) ログイン情報

作成日: 2025年11月8日

---

## 📝 共通パスワード

**すべてのアカウントのパスワード:**
```
password123
```

---

## 👨‍🎓 受験者（User）アカウント

### 📧 ログインURL
```
http://localhost/gs_code/gga/page/user/login.php
```

### アカウント一覧

| ID | 名前 | メールアドレス | チケット残数 |
|----|------|----------------|--------------|
| 1 | 山田 太郎 | yamada@example.com | 2 |
| 2 | 佐藤 花子 | sato@example.com | 4 |
| 3 | 鈴木 一郎 | suzuki@example.com | 5 |
| 4 | 高橋 さくら | takahashi@example.com | 3 |
| 5 | 伊藤 誠 | ito@example.com | 1 |

### 推奨テストアカウント
```
メール: yamada@example.com
パスワード: password123
```

---

## 👨‍🏫 キャリアコンサルタント（Trainer）アカウント

### 📧 ログインURL
```
http://localhost/gs_code/gga/page/trainer/login.php
```

### アカウント一覧

| ID | 名前 | メールアドレス |
|----|------|----------------|
| 1 | 田中 美咲 | tanaka.trainer@example.com |
| 2 | 佐藤 健 | sato.trainer@example.com |
| 3 | 鈴木 優子 | suzuki.trainer@example.com |
| 4 | 高橋 隆 | takahashi.trainer@example.com |
| 5 | 伊藤 京子 | ito.trainer@example.com |

### 推奨テストアカウント
```
メール: tanaka.trainer@example.com
パスワード: password123
```

---

## 💬 キャリア相談者（Client）アカウント

### 📧 ログインURL
```
http://localhost/gs_code/gga/page/client/login.php
```

### アカウント一覧

| ID | 名前 | メールアドレス |
|----|------|----------------|
| 1 | 山本 真理子 | yamamoto@example.com |

### 推奨テストアカウント
```
メール: yamamoto@example.com
パスワード: password123
```

**マイページURL:**
```
http://localhost/gs_code/gga/page/client/mypage.php
```

---

## 🚀 クイックスタート

### 1. トップページにアクセス
```
http://localhost/gs_code/gga/page/index.php
```

### 2. 受験者でログイン
1. 「キャリアコンサルタント受験者」カードの「ログイン」をクリック
2. メール: `yamada@example.com`
3. パスワード: `password123`
4. ログインして予約を作成

### 3. トレーナーでログイン（別のブラウザまたはシークレットモード）
1. 「キャリアコンサルタント有資格者」カードの「ログイン」をクリック
2. メール: `tanaka.trainer@example.com`
3. パスワード: `password123`
4. 予約を承認してフィードバックを入力

---

## 🧪 テストシナリオ

### シナリオ1: 予約作成と承認
1. **受験者**（yamada@example.com）でログイン
2. 「次の練習を予約リクエストをする」をクリック
3. 日付と時間を選択して予約作成
4. ログアウト
5. **トレーナー**（tanaka.trainer@example.com）でログイン
6. 承認待ちの予約を確認
7. 「承認」ボタンをクリック

### シナリオ2: フィードバック入力と閲覧
1. **トレーナー**でログイン
2. 確定済みの予約の「レポート入力」をクリック
3. 良かった点×3、改善点×3、目標×3、総評を入力
4. 「フィードバックを提出」
5. ログアウト
6. **受験者**でログイン
7. 「フィードバックを見る」をクリックして内容確認

---

## 🔄 ログアウト方法

### すべてのユーザータイプ共通
ナビゲーションバーの「ログアウト」リンクをクリック
または以下のURLに直接アクセス:
```
http://localhost/gs_code/gga/controller/logout.php
```

---

## 💾 データベース確認（参考）

### PhpMyAdminにアクセス
```
http://localhost/phpmyadmin
```

### ユーザー確認クエリ
```sql
USE careertre_db;

-- 受験者一覧
SELECT id, name, email, ticket_count FROM users;

-- トレーナー一覧
SELECT id, name, email FROM trainers;

-- 予約一覧
SELECT 
    r.id, 
    r.meeting_date, 
    r.status,
    u.name as user_name,
    t.name as trainer_name
FROM reserves r
LEFT JOIN users u ON r.user_id = u.id
LEFT JOIN trainers t ON r.trainer_id = t.id
ORDER BY r.created_at DESC;
```

---

## ⚠️ 注意事項

1. **同じブラウザで複数ユーザーのテストをする場合**
   - 必ずログアウトしてから別のアカウントでログイン
   - またはシークレットモード/プライベートブラウジングを使用

2. **パスワードを忘れた場合**
   - すべてのアカウントのパスワードは `password123` です

3. **データベースをリセットしたい場合**
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/gs_code/gga
   /Applications/XAMPP/xamppfiles/bin/mysql -u root < database/setup.sql
   /Applications/XAMPP/xamppfiles/bin/mysql -u root < database/dummy_data.sql
   ```

---

**🎉 準備完了！ログインしてテストを開始してください！**
