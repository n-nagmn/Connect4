<?php
// api_find_game.php
include 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$player_id = $input['my_player_id'] ?? null;

if (!$player_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Player ID is required']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM c4_games WHERE status = 'waiting' AND player_1_id != ? LIMIT 1");
$stmt->execute([$player_id]);
$waiting_game = $stmt->fetch();

if ($waiting_game) {
    $game_id = $waiting_game['game_id'];
    $player_1_id = $waiting_game['player_1_id'];
    $player_2_id = $player_id;

    $stmt = $pdo->prepare("UPDATE c4_games SET player_2_id = ?, status = 'playing', current_turn_id = ? WHERE game_id = ?");
    $stmt->execute([$player_2_id, $player_1_id, $game_id]);

    echo json_encode([
        'game_id' => $game_id,
        'role' => 'player_2'
    ]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM c4_games WHERE status = 'waiting' AND player_1_id = ?");
    $stmt->execute([$player_id]);
    $my_game = $stmt->fetch();

    if ($my_game) {
        echo json_encode([
            'game_id' => $my_game['game_id'],
            'role' => 'player_1'
        ]);
    } else {
        $initial_qmarks = [];
        while (count($initial_qmarks) < 6) {
            $r = rand(0, 6);
            $c = rand(0, 8);
            $key = "$r,$c";
            if (!in_array($key, $initial_qmarks)) {
                $initial_qmarks[] = $key;
            }
        }
        $initial_state = json_encode([
            'board' => array_fill(0, 7, array_fill(0, 9, null)),
            'scores' => ['1' => 0, '2' => 0],
            'questionMarks' => $initial_qmarks,
            'playerItems' => ['1' => [], '2' => []],
            'claimedPieces' => [],
            'pieceIdCounter' => 1
        ]);

        $stmt = $pdo->prepare("INSERT INTO c4_games (player_1_id, status, game_state) VALUES (?, 'waiting', ?)");
        $stmt->execute([$player_id, $initial_state]);
        $game_id = $pdo->lastInsertId();

        echo json_encode([
            'game_id' => $game_id,
            'role' => 'player_1'
        ]);
    }
}
?>