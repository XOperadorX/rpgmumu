<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])) exit(json_encode(['error'=>'Acesso negado']));
$adminID = $_SESSION['PlayerID'];

$stmt = sqlsrv_query($conn,"SELECT Role FROM Players WHERE PlayerID=?",[$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role']!=='admin') exit(json_encode(['error'=>'Acesso negado']));

$playerID = intval($_POST['playerID'] ?? 0);
if(!$playerID) exit(json_encode(['error'=>'Jogador inválido']));

$stmtCheck = sqlsrv_query($conn,"SELECT IsBanned FROM Players WHERE PlayerID=?",[$playerID]);
$player = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

if($player){
    $newStatus = $player['IsBanned'] ? 0 : 1;
    sqlsrv_query($conn,"UPDATE Players SET IsBanned=? WHERE PlayerID=?",[$newStatus,$playerID]);
    echo json_encode(['message'=> $newStatus ? 'Jogador bloqueado ✅' : 'Jogador desbloqueado 🔓']);
}else{
    echo json_encode(['error'=>'Jogador não encontrado']);
}
