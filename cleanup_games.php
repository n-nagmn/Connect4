<?php
// cleanup_games.php
include 'db_connect.php';

try {
     $pdo->exec("DELETE FROM c4_games WHERE status = 'waiting' AND created_at < (NOW() - INTERVAL 1 HOUR)");
     $pdo->exec("DELETE FROM c4_games WHERE status = 'playing' AND created_at < (NOW() - INTERVAL 2 HOUR)");
} catch (PDOException $e) {
     error_log("Cleanup failed: " . $e->getMessage());
}
?>