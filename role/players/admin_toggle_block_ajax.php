<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])) exit(json_encode(['error'=>'Acesso negado']));
$adminID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn,"SELECT Role FROM Players WHERE PlayerID=?",[$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role']!=='admin') exit(json_encode(['error'=>'Acesso negado']));

$playerID = $_POST['playerID'] ?? 0;
if(!$playerID) exit(json_encode(['error'=>'PlayerID inválido']));

// Puxa status atual
$stmtUser = sqlsrv_query($conn,"SELECT IsBanned FROM Players WHERE PlayerID=?",[$playerID]);
$user = sqlsrv_fetch_array($stmtUser, SQLSRV_FETCH_ASSOC);
if(!$user) exit(json_encode(['error'=>'Jogador não encontrado']));

$newStatus = $user['IsBanned'] ? 0 : 1;
$sqlUpdate = "UPDATE Players SET IsBanned=? WHERE PlayerID=?";
$stmtUpdate = sqlsrv_query($conn,$sqlUpdate,[$newStatus,$playerID]);

if($stmtUpdate){
    $msg = $newStatus ? 'Jogador bloqueado.' : 'Jogador desbloqueado.';
    echo json_encode(['message'=>$msg]);
} else {
    echo json_encode(['error'=>'Erro ao atualizar.']);
}
