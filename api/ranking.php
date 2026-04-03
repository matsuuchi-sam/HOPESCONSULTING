<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- DB設定（Xserverの管理画面で確認して書き換えてください） ---
$DB_HOST = 'localhost'; // ← Xserverのホスト名に要変更
$DB_NAME = 'xs086442_ranking';
$DB_USER = 'xs086442_game';
$DB_PASS = 'Hopesconsul0001!';
// -----------------------------------------------------------

$VALID_GAMES = ['typing', 'stroop', 'dodge'];
$MAX_RANKINGS = 3;

// レート制限（同一IP、同一ゲームで10秒以内の連続送信を拒否）
function checkRateLimit($pdo, $game, $ip) {
    $stmt = $pdo->prepare('SELECT created_at FROM rankings WHERE game = ? AND ip_address = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$game, $ip]);
    $row = $stmt->fetch();
    if ($row && (time() - strtotime($row['created_at'])) < 10) {
        return false;
    }
    return true;
}

// スコアの妥当性チェック
function validateScore($game, $score) {
    if (!is_numeric($score)) return false;
    $score = floatval($score);
    switch ($game) {
        case 'typing':
            return $score >= 0 && $score <= 300; // WPM
        case 'stroop':
            return $score >= 0 && $score <= 100; // 正解率%
        case 'dodge':
            return $score >= 0 && $score <= 9999; // 秒
        default:
            return false;
    }
}

// ストループ用：同スコア時のサブスコア（平均速度、低い方が上位）
function getOrderClause($game) {
    switch ($game) {
        case 'stroop':
            return 'score DESC, sub_score ASC'; // 正解率高い→平均速度速い
        case 'typing':
            return 'score DESC'; // WPM高い
        case 'dodge':
            return 'score DESC'; // 秒数長い
        default:
            return 'score DESC';
    }
}

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// テーブル自動作成
$pdo->exec("CREATE TABLE IF NOT EXISTS rankings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game VARCHAR(20) NOT NULL,
    initials VARCHAR(5) NOT NULL,
    score FLOAT NOT NULL,
    sub_score FLOAT DEFAULT NULL,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_game_score (game, score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

// ===== GET: ランキング取得 =====
if ($method === 'GET') {
    $game = $_GET['game'] ?? '';
    if (!in_array($game, $VALID_GAMES)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid game']);
        exit;
    }

    $order = getOrderClause($game);
    $stmt = $pdo->prepare("SELECT initials, score, sub_score FROM rankings WHERE game = ? ORDER BY $order LIMIT ?");
    $stmt->bindValue(1, $game);
    $stmt->bindValue(2, $MAX_RANKINGS, PDO::PARAM_INT);
    $stmt->execute();
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['rankings' => $rankings]);
    exit;
}

// ===== POST: スコア登録 =====
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $game = $input['game'] ?? '';
    $initials = strtoupper($input['initials'] ?? '');
    $score = $input['score'] ?? null;
    $subScore = $input['sub_score'] ?? null;

    // バリデーション
    if (!in_array($game, $VALID_GAMES)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid game']);
        exit;
    }
    if (!preg_match('/^[A-Za-z0-9.\-]{1,5}$/', $initials)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid initials']);
        exit;
    }
    if (!validateScore($game, $score)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid score']);
        exit;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // レート制限
    if (!checkRateLimit($pdo, $game, $ip)) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests']);
        exit;
    }

    $score = floatval($score);
    $subScore = $subScore !== null ? floatval($subScore) : null;

    // 現在のTOP3を取得して、ランクインするか判定
    $order = getOrderClause($game);
    $stmt = $pdo->prepare("SELECT id, score, sub_score FROM rankings WHERE game = ? ORDER BY $order LIMIT ?");
    $stmt->bindValue(1, $game);
    $stmt->bindValue(2, $MAX_RANKINGS, PDO::PARAM_INT);
    $stmt->execute();
    $current = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $qualifies = false;
    if (count($current) < $MAX_RANKINGS) {
        $qualifies = true;
    } else {
        // 最下位と比較
        $worst = $current[count($current) - 1];
        if ($game === 'stroop') {
            if ($score > $worst['score'] || ($score == $worst['score'] && $subScore !== null && $subScore < $worst['sub_score'])) {
                $qualifies = true;
            }
        } else {
            if ($score > $worst['score']) {
                $qualifies = true;
            }
        }
    }

    if (!$qualifies) {
        echo json_encode(['ranked' => false, 'message' => 'Score did not qualify']);
        exit;
    }

    // 登録
    $stmt = $pdo->prepare('INSERT INTO rankings (game, initials, score, sub_score, ip_address) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$game, $initials, $score, $subScore, $ip]);

    // 上位3件以外を削除
    $stmt = $pdo->prepare("SELECT id FROM rankings WHERE game = ? ORDER BY $order");
    $stmt->execute([$game]);
    $all = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($all) > $MAX_RANKINGS) {
        $toDelete = array_slice($all, $MAX_RANKINGS);
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $pdo->prepare("DELETE FROM rankings WHERE id IN ($placeholders)")->execute($toDelete);
    }

    // 更新後のランキング返却
    $stmt = $pdo->prepare("SELECT initials, score, sub_score FROM rankings WHERE game = ? ORDER BY $order LIMIT ?");
    $stmt->bindValue(1, $game);
    $stmt->bindValue(2, $MAX_RANKINGS, PDO::PARAM_INT);
    $stmt->execute();
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ranked' => true, 'rankings' => $rankings]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
