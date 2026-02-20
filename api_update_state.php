<?php
// api_update_state.php
include 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$game_id = $input['game_id'] ?? null;
$game_state = $input['game_state'] ?? null;
$current_turn_id = $input['current_turn_id'] ?? null;
$status = $input['status'] ?? null;
$winner_id = $input['winner_id'] ?? null;

if (!$game_id || !$game_state || !$current_turn_id || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$stmt = $pdo->prepare("UPDATE c4_games SET game_state = ?, current_turn_id = ?, status = ?, winner_id = ? WHERE game_id = ?");
$stmt->execute([$game_state, $current_turn_id, $status, $winner_id, $game_id]);

echo json_encode(['status' => 'success']);
?>