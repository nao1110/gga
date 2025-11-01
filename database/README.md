# データベース設計書

## データベース名
`careertre_db`

## テーブル一覧

### 1. users（受験者ユーザー）
キャリアコンサルタント試験受験者の情報を管理

| カラム名 | 型 | 制約 | デフォルト | 説明 |
|---------|-----|------|-----------|------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | - | ユーザーID |
| name | VARCHAR(100) | NOT NULL | - | ユーザー名 |
| email | VARCHAR(255) | NOT NULL, UNIQUE | - | メールアドレス |
| password | VARCHAR(255) | NOT NULL | - | パスワード（ハッシュ化） |
| google_id | VARCHAR(255) | NULL | NULL | Google ID（OAuth用） |
| ticket_count | INT | NOT NULL | 5 | チケット数（練習回数） |
| is_active | BOOLEAN | NOT NULL | TRUE | アクティブフラグ |
| created_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP | 作成日時 |
| updated_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | 更新日時 |

**インデックス:**
- `idx_email` (email)
- `idx_google_id` (google_id)

---

### 2. trainers（トレーナー）
キャリアコンサルタント有資格者の情報を管理

| カラム名 | 型 | 制約 | デフォルト | 説明 |
|---------|-----|------|-----------|------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | - | トレーナーID |
| name | VARCHAR(100) | NOT NULL | - | トレーナー名 |
| email | VARCHAR(255) | NOT NULL, UNIQUE | - | メールアドレス |
| password | VARCHAR(255) | NOT NULL | - | パスワード（ハッシュ化） |
| google_id | VARCHAR(255) | NULL | NULL | Google ID（OAuth用） |
| is_active | BOOLEAN | NOT NULL | TRUE | アクティブフラグ |
| created_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP | 作成日時 |
| updated_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | 更新日時 |

**インデックス:**
- `idx_email` (email)
- `idx_google_id` (google_id)

---

### 3. personas（ペルソナ）
面接練習用のペルソナ情報（5種類）

| カラム名 | 型 | 制約 | デフォルト | 説明 |
|---------|-----|------|-----------|------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | - | ペルソナID（1-5） |
| persona_name | VARCHAR(100) | NOT NULL | - | ペルソナ名 |
| age | INT | NOT NULL | - | 年齢 |
| family_structure | VARCHAR(255) | NOT NULL | - | 家族構成 |
| job | VARCHAR(255) | NOT NULL | - | 業種・職種 |
| situation | TEXT | NOT NULL | - | 相談状況・テーマ |
| created_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP | 作成日時 |
| updated_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | 更新日時 |

**初期データ:**
1. 佐藤 悠斗（24歳）- リアリティショック
2. 田中 恵子（48歳）- 介護と仕事の両立
3. 中村 健太（35歳）- 非正規雇用の不安
4. 小林 雅人（53歳）- 早期退職検討
5. 山本 義雄（61歳）- 再雇用での不適応

---

### 4. reserves（予約）
実技練習の予約・実施情報

| カラム名 | 型 | 制約 | デフォルト | 説明 |
|---------|-----|------|-----------|------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | - | 予約ID |
| meeting_date | DATETIME | NOT NULL | - | 面談日時 |
| user_id | INT | NOT NULL, FOREIGN KEY | - | ユーザーID |
| trainer_id | INT | NOT NULL, FOREIGN KEY | - | トレーナーID |
| persona_id | INT | NOT NULL, FOREIGN KEY | - | ペルソナID |
| meeting_url | VARCHAR(500) | NULL | NULL | オンライン面談URL |
| status | ENUM | NOT NULL | 'pending' | 予約ステータス |
| created_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP | 作成日時 |
| updated_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | 更新日時 |

**status の値:**
- `pending`: 承認待ち
- `confirmed`: 承認済み
- `completed`: 完了
- `cancelled`: キャンセル

**外部キー:**
- `user_id` → users(id) ON DELETE CASCADE
- `trainer_id` → trainers(id) ON DELETE CASCADE
- `persona_id` → personas(id) ON DELETE RESTRICT

**インデックス:**
- `idx_user_id` (user_id)
- `idx_trainer_id` (trainer_id)
- `idx_meeting_date` (meeting_date)
- `idx_status` (status)

---

### 5. feedbacks（フィードバック）
トレーナーから受験者へのフィードバック

| カラム名 | 型 | 制約 | デフォルト | 説明 |
|---------|-----|------|-----------|------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | - | フィードバックID |
| reserve_id | INT | NOT NULL, FOREIGN KEY | - | 予約ID |
| trainer_id | INT | NOT NULL, FOREIGN KEY | - | トレーナーID |
| comment | TEXT | NOT NULL | - | フィードバック内容（JSON） |
| created_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP | 作成日時 |
| updated_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | 更新日時 |

**comment フィールドのJSON構造例:**
```json
{
  "strengths": [
    "傾聴姿勢が非常に良好",
    "うなずきやあいづちのタイミングが適切",
    "共感的な言葉かけができている"
  ],
  "improvements": [
    "質問の組み立てをもう少し整理",
    "本質的な課題に迫る深い質問を意識",
    "時間配分に注意"
  ],
  "next_goals": [
    "質問力の向上",
    "アクションプランへの導き方",
    "効果的な面談構成"
  ],
  "overall_comment": "全体として良い面談ができていました..."
}
```

**外部キー:**
- `reserve_id` → reserves(id) ON DELETE CASCADE
- `trainer_id` → trainers(id) ON DELETE CASCADE

**インデックス:**
- `idx_reserve_id` (reserve_id)
- `idx_trainer_id` (trainer_id)

---

### 6. reports（自己フィードバック）
受験者の自己フィードバック・振り返り

| カラム名 | 型 | 制約 | デフォルト | 説明 |
|---------|-----|------|-----------|------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | レポートID |
| reserve_id | BIGINT | NOT NULL | - | 予約ID |
| user_id | BIGINT | NOT NULL | - | ユーザーID |
| comment | TEXT | NOT NULL | - | 自己フィードバック内容（JSON） |
| created_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP | 作成日時 |
| updated_at | DATETIME | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | 更新日時 |

**comment フィールドのJSON構造例:**
```json
{
  "satisfaction": 4,
  "strengths": "傾聴を意識できた点",
  "challenges": "質問の組み立てが難しかった",
  "learnings": "相手の話を最後まで聞く重要性",
  "next_goals": "もっと深い質問をする"
}
```

**インデックス:**
- `idx_reserve_id` (reserve_id)
- `idx_user_id` (user_id)

---

## ER図（関連図）

```
users (受験者)
  ↓ 1:N
reserves (予約) ← N:1 → trainers (トレーナー)
  ↓ 1:1            ↓ N:1
feedbacks          personas (ペルソナ)
  
reserves (予約)
  ↓ 1:1
reports (自己フィードバック)
```

## セットアップ方法

1. MySQLにログイン
```bash
mysql -u root -p
```

2. SQLファイルを実行
```bash
mysql -u root -p < database/setup.sql
```

または、MySQL内で
```sql
SOURCE /Applications/XAMPP/xamppfiles/htdocs/gs_code/gga/database/setup.sql;
```

3. データベース確認
```sql
USE careertre_db;
SHOW TABLES;
```

## 注意事項

- パスワードは必ずハッシュ化して保存（`password_hash()`関数を使用）
- JSON形式のデータは、PHPで `json_encode()` / `json_decode()` を使用
- 外部キー制約により、データの整合性を保証
- `ticket_count` は予約時に減算、キャンセル時に加算
- `is_active` フラグでアカウントの有効/無効を管理
