<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['error'=>'⛔ Acesso negado. Faça login primeiro.']);
    exit;
}

$playerID = intval($_SESSION['PlayerID']);

// Pega personagem do jogador
$q = sqlsrv_query($conn, "SELECT TOP 1 CharID, Name, Level, HP, MaxHP, Power FROM Characters WHERE PlayerID = ?", [$playerID]);
$player = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC);
if (!$player) {
    echo json_encode(['error'=>'Personagem não encontrado']);
    exit;
}

$playerHP = intval($player['HP']);
$playerMaxHP = intval($player['MaxHP']);
$playerPower = intval($player['Power']);

// Recebe inimigo
$enemyID = isset($_POST['enemyID']) ? intval($_POST['enemyID']) : 0;
if ($enemyID <= 0) {
    echo json_encode(['error'=>'EnemyID inválido']);
    exit;
}

// Busca inimigo no banco
$qe = sqlsrv_query($conn, "SELECT EnemyID, Name, Level, HP, MaxHP, Attack FROM Enemies WHERE EnemyID = ?", [$enemyID]);
$enemy = sqlsrv_fetch_array($qe, SQLSRV_FETCH_ASSOC);

// Se não achar no banco, cria inimigo de teste
if (!$enemy) {
    $enemy = [
        'EnemyID'=>$enemyID,
        'Name'=>'Teste Goblin',
        'Level'=>1,
        'HP'=>50,
        'MaxHP'=>50,
        'Attack'=>5
    ];
}

$enemyHP = intval($enemy['HP']);
$enemyAttack = intval($enemy['Attack']);

// ===== Batalha simples =====
// Dano do jogador
$damageToEnemy = rand($playerPower-2, $playerPower+2);
$enemyHP -= $damageToEnemy;
if ($enemyHP < 0) $enemyHP = 0;

// Dano do inimigo
$damageToPlayer = rand($enemyAttack-2, $enemyAttack+2);
$playerHP -= $damageToPlayer;
if ($playerHP < 0) $playerHP = 0;

// Atualiza HP do jogador no banco
sqlsrv_query($conn, "UPDATE Characters SET HP = ? WHERE CharID = ?", [$playerHP, $player['CharID']]);

// Retorna resultado
echo json_encode([
    'ok'=>true,
    'enemy'=>[
        'id'=>$enemy['EnemyID'],
        'name'=>$enemy['Name'],
        'level'=>$enemy['Level'],
        'hp'=>$enemyHP,
        'maxhp'=>$enemy['MaxHP']
    ],
    'damageToEnemy'=>$damageToEnemy,
    'damageToPlayer'=>$damageToPlayer,
    'player'=>[
        'hp'=>$playerHP,
        'maxhp'=>$playerMaxHP
    ],
    'log'=>"O jogador causou $damageToEnemy de dano, recebeu $damageToPlayer de dano."
]);
