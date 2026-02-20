# Connect 4 Online - 構築・操作ガイド

PHPとMySQLを使用した、サーバー対戦型のConnect 4（四目並べカスタム）ゲームです。標準的なルールに加え、アイテムやスコア制、特殊な盤面ギミックが搭載されています。

## 1. 構成ファイル

プロジェクトディレクトリ（例: `/var/www/html/connect4/`）に以下のファイルを配置してください。

* `db_connect.php`: データベース接続設定
* `api_find_game.php`: マッチング・ゲーム作成API
* `api_get_state.php`: 最新のゲーム状態取得API
* `api_update_state.php`: ゲーム進行（盤面更新）API
* `index.html`: ゲームクライアント（GUI）
* `cleanup_games.php`: 放置されたゲームの削除スクリプト（Cron用）

## 2. データベースのセットアップ

MySQLにログインし、以下のSQLを実行してデータベースとテーブルを作成します。

```sql
-- データベースとユーザーの作成
CREATE DATABASE connect4_db;
CREATE USER 'c4_user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON connect4_db.* TO 'c4_user'@'localhost';
FLUSH PRIVILEGES;

USE connect4_db;

-- ゲーム管理テーブル
CREATE TABLE c4_games (
  game_id INT AUTO_INCREMENT PRIMARY KEY,
  player_1_id VARCHAR(255),
  player_2_id VARCHAR(255),
  current_turn_id VARCHAR(255),
  status VARCHAR(20) NOT NULL DEFAULT 'waiting', -- waiting, playing, finished
  winner_id VARCHAR(255),
  game_state TEXT,                               -- JSON形式で盤面やスコアを保存
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 3. サーバー環境の設定

### 権限の設定
Webサーバー（Apache/Nginx）がファイルを読み込めるよう権限を設定します。
```bash
sudo chown -R www-data:www-data /var/www/html/connect4/
```

### 定期清掃の設定 (推奨)
放置された古いデータを自動削除するために Cron を設定します。
```bash
# crontabの編集
crontab -e

# 以下の行を追加（30分ごとに実行）
*/30 * * * * php /var/www/html/connect4/cleanup_games.php
```

## 4. 遊び方

### ゲームの開始
1.  ブラウザで `http://(サーバーのIP)/connect4/index.html` にアクセスします。
2.  **[Matchmaking]** ボタンを押します。
    * 待機中のプレイヤーがいれば対戦が始まります。
    * いなければ「Waiting...」となり、他のプレイヤーが参加するまで待機します。

### 基本ルール
* **交互に着手**: 自分のターンの時に、落としたい列をクリックします。
* **勝利条件**: 縦・横・斜めのいずれかに同じ色の駒を4つ並べるとスコアが入ります。規定スコア（デフォルト3点）を先取したプレイヤーの勝利です。
* **重力**: 駒は常に列の最下部に積み上がります。
* **盤面爆発**: 盤面が全て埋まると、下部の数行が消滅し、残った駒が落下するギミックが発動します。

### 特殊ギミック
* **? マス**: 駒を置くとランダムでアイテムを取得できます。
* **アイテムの使用**: 自分のターンの時にアイテムボタンをクリックして選択状態にし、列をクリックすることで発動します。
    * `x2`: 一度に2つの駒を落とします。
    * `💣`: 着手地点の周囲の駒を破壊します。
    * `🌪`: 盤面全体の駒をランダムにシャッフルします。
    * `⇔`: 着手地点に隣接する駒の色（所有権）を反転させます。