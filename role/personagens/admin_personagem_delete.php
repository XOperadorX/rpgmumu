<?php
session_start();
include "../db.php";
header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['success'=>false,'message'=>'⛔ Acesso negado']);
    exit;
}

$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID=?", [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role'] !== 'admin'){
    echo json_encode(['success'=>false,'message'=>'⛔ Acesso negado']);
    exit;
}

if(!isset($_POST['CharID'])){
    echo json_encode(['success'=>false,'message'=>'❌ CharID não fornecido']);
    exit;
}

$charID = intval($_POST['CharID']);

// Deleta itens e logs
sqlsrv_query($conn, "DELETE FROM Items WHERE CharID=?", [$charID]);
sqlsrv_query($conn, "DELETE FROM DungeonLog WHERE CharID=?", [$charID]);
// Deleta personagem
sqlsrv_query($conn, "DELETE FROM Characters WHERE CharID=?", [$charID]);

echo json_encode(['success'=>true,'message'=>'✅ Personagem deletado com sucesso']);
?>
