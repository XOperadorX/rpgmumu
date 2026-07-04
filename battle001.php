<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['error' => '⛔ Acesso negado.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$enemyID = intval($_POST['enemyID'] ?? 0);

// Pega personagem do jogador
$stmtChar = sqlsrv_query($conn, "
    SELECT TOP 1 CharID, Name, Power, HP, MaxHP, Level 
    FROM dbo.Characters 
    WHERE PlayerID = ?", [$playerID]);
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
if (!$char) {
    echo json_encode(['error' => 'Personagem não encontrado.']);
    exit;
}

// Pega inimigo
$stmtEnemy = sqlsrv_query($conn, "
    SELECT EnemyID, Name, HP, MaxHP, Level, Attack, Defense 
    FROM dbo.Enemies WHERE EnemyID = ?", [$enemyID]);
$enemy = sqlsrv_fetch_array($stmtEnemy, SQLSRV_FETCH_ASSOC);
if (!$enemy) {
    echo json_encode(['error' => 'Inimigo não encontrado.']);
    exit;
}

// ======== Lógica de batalha ========
$playerAtk = $char['Power'] + rand(1, 6);
$enemyAtk = $enemy['Attack'] + rand(1, 4);

$damageToEnemy = max(0, $playerAtk - $enemy['Defense']);
$damageToPlayer = max(0, $enemyAtk - intval($char['Level'] / 2));

$newEnemyHP = max(0, $enemy['HP'] - $damageToEnemy);
$newPlayerHP = max(0, $char['HP'] - $damageToPlayer);

// Atualiza HPs no banco
sqlsrv_query($conn, "UPDATE dbo.Enemies SET HP = ? WHERE EnemyID = ?", [$newEnemyHP, $enemyID]);
sqlsrv_query($conn, "UPDATE dbo.Characters SET HP = ? WHERE CharID = ?", [$newPlayerHP, $char['CharID']]);

// Se inimigo morreu
$log = "";
$expGain = 0;
if ($newEnemyHP <= 0) {
    $expGain = $enemy['Level'] * 10;
    $log .= "{$enemy['Name']} foi derrotado! +{$expGain} XP. ";
    sqlsrv_query($conn, "UPDATE dbo.Characters SET Exp = Exp + ? WHERE CharID = ?", [$expGain, $char['CharID']]);
    sqlsrv_query($conn, "DELETE FROM dbo.EnemyPositions WHERE EnemyID = ?", [$enemyID]); // remove do mapa
}

// Monta resposta JSON
echo json_encode([
    'enemy' => [
        'id' => $enemyID,
        'name' => $enemy['Name'],
        'hp' => $newEnemyHP,
        'maxhp' => $enemy['MaxHP']
    ],
    'player' => [
        'hp' => $newPlayerHP,
        'maxhp' => $char['MaxHP']
    ],
    'damageToEnemy' => $damageToEnemy,
    'damageToPlayer' => $damageToPlayer,
    'log' => $log
]);
?>
