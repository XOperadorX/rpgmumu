<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];
$mensagem = "";

// Pega um personagem aleatório do player
$stmt = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID=?", [$playerID]);
if(!$stmt || !sqlsrv_has_rows($stmt)){
    die("Você precisa ter pelo menos um personagem para entrar na dungeon.<br><a href='dashboard.php'>⬅️ Voltar</a>");
}
$char = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Dungeon: XP e item aleatório
$xp = rand(10,50);
$Items = ['Espada','Escudo','Capacete','Armadura','Gluva','Calça','Asa','Pet','Anel','Pingente','Colar'];
$item = $Items[array_rand($Items)];

// Atualiza XP (Level simples)
$newExp = $char['Exp'] + $xp;
$newLevel = $char['Level'];
while($newExp >= 100){
    $newExp -= 100;
    $newLevel++;
}

// Atualiza Characters
$sqlUpdate = "UPDATE Characters SET Level=?, Exp=? WHERE CharID=?";
sqlsrv_query($conn, $sqlUpdate, [$newLevel, $newExp, $char['CharID']]);

// Adiciona item ao inventário
$sqlItem = "INSERT INTO Items (CharID, Name) VALUES (?, ?)";
sqlsrv_query($conn, $sqlItem, [$char['CharID'], $item]);

// Log da dungeon
$sqlLog = "INSERT INTO DungeonLog (CharID, XP, Item) VALUES (?, ?, ?)";
sqlsrv_query($conn, $sqlLog, [$char['CharID'],$xp,$item]);

$mensagem = "✅ Você ganhou {$xp} XP e encontrou um item: {$item}";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dungeon</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>🗡️ Dungeon</h2>
<p><?= $mensagem ?></p>
<a href="dashboard.php">⬅️ Voltar</a>
<a href="log_dungeon.php">📜 Ver Log da Dungeon</a>
</body>
</html>
