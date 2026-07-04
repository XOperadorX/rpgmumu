<?php
session_start();
include "db.php";

header('Content-Type: application/json');

// Verifica login
if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['success'=>false,'message'=>"⛔ Faça login primeiro."]);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$slotID = isset($_POST['slotID']) ? intval($_POST['slotID']) : 0;

if($slotID <= 0){
    echo json_encode(['success'=>false,'message'=>"Slot inválido."]);
    exit;
}

// Busca slot e fruta
$sql = "
SELECT pf.FrutaID, pf.DataPlantio, f.Nome, f.TempoCrescimento
FROM dbo.PlantacaoFazenda pf
JOIN dbo.Frutas f ON pf.FrutaID = f.FrutaID
WHERE pf.PlayerID = ? AND pf.SlotID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID, $slotID]);

if(!$stmt){
    echo json_encode(['success'=>false,'message'=>"Erro ao consultar o slot."]);
    exit;
}

$slot = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$slot){
    echo json_encode(['success'=>false,'message'=>"Slot vazio ou fruta inválida."]);
    exit;
}

// Verifica se a fruta está pronta para colher
$plantio = $slot['DataPlantio'] instanceof DateTime ? $slot['DataPlantio'] : new DateTime($slot['DataPlantio']);
$colheita = clone $plantio;
$colheita->modify("+{$slot['TempoCrescimento']} minutes");
$agora = new DateTime("now");

if($agora < $colheita){
    echo json_encode(['success'=>false,'message'=>"⏳ A fruta ainda não está pronta!"]);
    exit;
}

// Atualiza inventário
$sqlCheck = "SELECT Quantidade FROM dbo.InventarioFrutas WHERE PlayerID = ? AND FrutaID = ?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, [$playerID, $slot['FrutaID']]);
$inv = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

if($inv){
    $sqlUpdate = "UPDATE dbo.InventarioFrutas SET Quantidade = Quantidade + 1 WHERE PlayerID = ? AND FrutaID = ?";
    sqlsrv_query($conn, $sqlUpdate, [$playerID, $slot['FrutaID']]);
} else {
    $sqlInsert = "INSERT INTO dbo.InventarioFrutas (PlayerID, FrutaID, Quantidade) VALUES (?, ?, 1)";
    sqlsrv_query($conn, $sqlInsert, [$playerID, $slot['FrutaID']]);
}

// Limpa o slot de plantio
$sqlClear = "UPDATE dbo.PlantacaoFazenda SET FrutaID = NULL, DataPlantio = NULL WHERE PlayerID = ? AND SlotID = ?";
sqlsrv_query($conn, $sqlClear, [$playerID, $slotID]);

echo json_encode(['success'=>true,'message'=>"🍎 Colhido com sucesso: {$slot['Nome']}!"]);
?>
