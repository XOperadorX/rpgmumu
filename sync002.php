<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// ======== Pega personagem atual ========
$stmtChar = sqlsrv_query($conn, "
    SELECT TOP 1 
        c.CharID, c.Name, c.Class, c.Level, c.HP, c.MaxHP, 
        c.Mana, c.MaxMana, c.Power, c.Exp,
        p.Xpos, p.Ypos
    FROM dbo.Characters c
    JOIN dbo.CharacterPositions p 
        ON c.PlayerID = p.PlayerID AND c.CharID = p.CharID
    WHERE c.PlayerID = ?
", [$playerID]);

$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);

// ======== Pega inimigos ========
$enemies = [];
$stmtEnemies = sqlsrv_query($conn, "
    SELECT e.EnemyID, e.Name, e.HP, e.MaxHP, e.Level, 
           p.Xpos, p.Ypos
    FROM dbo.Enemies e
    JOIN dbo.EnemyPositions p ON e.EnemyID = p.EnemyID
    WHERE e.HP > 0
");
if ($stmtEnemies !== false) {
    while ($row = sqlsrv_fetch_array($stmtEnemies, SQLSRV_FETCH_ASSOC)) {
        $enemies[] = $row;
    }
}

// ======== Pega outros jogadores online ========
$others = [];
$stmtOthers = sqlsrv_query($conn, "
    SELECT c.CharID, c.Name, c.Class, c.Level, c.HP, c.Mana,
           p.Xpos, p.Ypos, c.PlayerID
    FROM dbo.Characters c
    JOIN dbo.CharacterPositions p 
        ON c.PlayerID = p.PlayerID AND c.CharID = p.CharID
    WHERE c.PlayerID <> ?
", [$playerID]);
if ($stmtOthers !== false) {
    while ($row = sqlsrv_fetch_array($stmtOthers, SQLSRV_FETCH_ASSOC)) {
        $others[] = $row;
    }
}

// ======== Resposta JSON ========
echo json_encode([
    'char' => $char,
    'enemies' => $enemies,
    'others' => $others
]);
