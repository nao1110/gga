# フィードバック動的反映テスト手順

## 修正内容

### 問題
トレーナーが入力したフィードバックが、受験者のマイページに動的に反映されない。

### 原因
1. 受験者マイページが`status = 'completed'`のみを「完了済みセッション」として表示
2. フィードバックが存在しても、ステータスが`confirmed`の場合は「実施予定」として表示されてしまう

### 解決策
1. **受験者マイページのロジック修正** (`page/user/mypage.php`)
   - 完了済みセッション: `status = 'completed'` **または** `フィードバックが存在する`
   - 実施予定セッション: `status = 'confirmed'` **かつ** `フィードバックがない`

2. **フィードバック処理の確実性向上** (`feedback_process.php`)
   - フィードバックを更新する場合も、予約のステータスを`completed`に確実に更新

---

## テストケース

### 前提条件
```sql
-- 現在のデータベース状態
-- 予約ID=1: user_id=1（山田太郎）、status='confirmed'、feedback_id=NULL
-- 予約ID=7: user_id=1（山田太郎）、status='completed'、feedback_id=2（あり）
```

### テスト1: 初回フィードバック入力の反映確認

#### 手順
1. **受験者（山田太郎）でログイン**
   - URL: http://localhost/gs_code/gga/page/user/login.php
   - メール: yamada@example.com
   - パスワード: password123

2. **マイページの初期状態を確認**
   - 「予定されている練習（確定済み）」に予約ID=1が表示される
   - 「過去の練習履歴・フィードバック」に予約ID=7が「レポート受領済み」で表示される

3. **トレーナー（田中美咲）でログイン**
   - URL: http://localhost/gs_code/gga/page/trainer/login.php
   - メール: tanaka.trainer@example.com
   - パスワード: password123

4. **予約ID=1のフィードバックを入力**
   - トレーナーマイページで「実施予定の実技練習」セクション
   - 予約ID=1の「レポート入力」ボタンをクリック
   - フィードバックフォームに入力:
     ```
     1. 態度・傾聴: テストフィードバック1
     2. 問題把握: テストフィードバック2
     3. 具体的展開: テストフィードバック3
     次回へのアドバイス: テストアドバイス
     ```
   - 「フィードバックを提出」をクリック

5. **データベースで確認**
   ```sql
   SELECT r.id, r.status, f.id as feedback_id 
   FROM reserves r 
   LEFT JOIN feedbacks f ON r.id = f.reserve_id 
   WHERE r.id = 1;
   ```
   - **期待結果**: `status='completed'`、`feedback_id`に値が入る

6. **受験者マイページで確認**
   - 受験者（山田太郎）のマイページをリロード
   - **期待結果**: 
     - 予約ID=1が「予定されている練習」から消える
     - 予約ID=1が「過去の練習履歴・フィードバック」に「レポート受領済み」で表示される

---

### テスト2: フィードバック更新の反映確認

#### 手順
1. **トレーナー（田中美咲）でログイン**
   
2. **予約ID=7のフィードバックを更新**
   - トレーナーマイページで「完了済み・レポート提出履歴」セクション
   - 予約ID=7の「レポートを見る」をクリック
   - フィードバック内容を編集
   - 「フィードバックを更新」をクリック

3. **データベースで確認**
   ```sql
   SELECT r.id, r.status, f.updated_at 
   FROM reserves r 
   LEFT JOIN feedbacks f ON r.id = f.reserve_id 
   WHERE r.id = 7;
   ```
   - **期待結果**: `status='completed'`（維持）、`updated_at`が更新される

4. **受験者マイページで確認**
   - 受験者（山田太郎）のマイページをリロード
   - 予約ID=7の「フィードバックを見る」をクリック
   - **期待結果**: 更新されたフィードバック内容が表示される

---

### テスト3: エッジケース - confirmed状態でフィードバックあり

このケースは、何らかの理由でステータスが`confirmed`のままフィードバックが存在する場合です。

#### 手順（手動でシミュレート）
```sql
-- テスト用データを作成
INSERT INTO reserves (user_id, trainer_id, persona_id, meeting_date, status, created_at, updated_at)
VALUES (1, 1, 1, '2025-11-10 10:00:00', 'confirmed', NOW(), NOW());

SET @reserve_id = LAST_INSERT_ID();

INSERT INTO feedbacks (reserve_id, trainer_id, comment, created_at, updated_at)
VALUES (@reserve_id, 1, '{"attitude_comment":"test"}', NOW(), NOW());
```

#### 確認
1. 受験者（山田太郎）のマイページをリロード
2. **期待結果**: この予約が「過去の練習履歴・フィードバック」に「レポート受領済み」で表示される
   - （修正前は「予定されている練習」に表示されてしまう）

---

## 自動確認用SQLクエリ

### 受験者マイページのロジックをSQLで再現
```sql
-- ユーザーID=1の予約を取得
SELECT 
    r.id,
    r.meeting_date,
    r.status,
    t.name as trainer_name,
    CASE 
        WHEN f.comment IS NOT NULL THEN 'あり' 
        ELSE 'なし' 
    END as has_feedback,
    -- 完了済みセッション判定
    CASE 
        WHEN r.status = 'completed' THEN '完了済み'
        WHEN r.status = 'confirmed' AND f.comment IS NOT NULL THEN '完了済み（FBあり）'
        WHEN r.status = 'confirmed' AND f.comment IS NULL THEN '実施予定'
        WHEN r.status = 'pending' THEN '承認待ち'
        ELSE '不明'
    END as display_section
FROM reserves r
LEFT JOIN trainers t ON r.trainer_id = t.id
LEFT JOIN feedbacks f ON r.id = f.reserve_id
WHERE r.user_id = 1
ORDER BY r.meeting_date DESC;
```

### 期待される出力例
```
+----+---------------------+-----------+---------------+--------------+------------------------+
| id | meeting_date        | status    | trainer_name  | has_feedback | display_section        |
+----+---------------------+-----------+---------------+--------------+------------------------+
|  1 | 2025-11-15 14:00:00 | confirmed | 田中 美咲     | なし         | 実施予定               |
|  7 | 2025-10-20 14:00:00 | completed | 田中 美咲     | あり         | 完了済み               |
+----+---------------------+-----------+---------------+--------------+------------------------+
```

### フィードバック入力後の期待される出力
```
+----+---------------------+-----------+---------------+--------------+------------------------+
| id | meeting_date        | status    | trainer_name  | has_feedback | display_section        |
+----+---------------------+-----------+---------------+--------------+------------------------+
|  1 | 2025-11-15 14:00:00 | completed | 田中 美咲     | あり         | 完了済み               |
|  7 | 2025-10-20 14:00:00 | completed | 田中 美咲     | あり         | 完了済み               |
+----+---------------------+-----------+---------------+--------------+------------------------+
```

---

## 実装の利点

### 1. 即時反映
トレーナーがフィードバックを入力した瞬間から、受験者がページをリロードすれば反映される

### 2. データ整合性
フィードバックが存在する場合、必ず「完了済みセッション」として扱われる

### 3. 柔軟性
`status`の更新に失敗しても、フィードバックの存在で正しく分類される

### 4. ユーザー体験の向上
受験者は、トレーナーがフィードバックを入力したことを即座に確認できる

---

**テスト実施日**: 2025年11月8日  
**修正ファイル**:
- `/page/user/mypage.php`
- `/page/trainer/mypage/reserve/feedback/feedback_process.php`
