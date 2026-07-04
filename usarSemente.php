<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['success'=>false,'message'=>"⛔ Faça login primeiro."]);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$frutaID = $_POST['frutaID'] ?? null;

if (!$frutaID) {
    echo json_encode(['success'=>false,'message'=>"Selecione uma semente para usar."]);
    exit;
}

sqlsrv_begin_transaction($conn);

try {
    // Verifica se possui a semente
    $sqlCheck = "SELECT Quantidade FROM dbo.InventarioFrutas WHERE PlayerID = ? AND FrutaID = ?";
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, [$playerID, $frutaID]);
    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    if (!$rowCheck || $rowCheck['Quantidade'] <= 0) throw new Exception("❌ Você não possui esta semente.");

    // Deduz semente
    sqlsrv_query($conn, "UPDATE dbo.InventarioFrutas SET Quantidade = Quantidade - 1 WHERE PlayerID = ? AND FrutaID = ?", [$playerID, $frutaID]);

    // Insere plantio
    sqlsrv_query($conn, "INSERT INTO dbo.PlantacaoFazenda (PlayerID, FrutaID, DataPlantio) VALUES (?, ?, GETDATE())", [$playerID, $frutaID]);

    sqlsrv_commit($conn);

    echo json_encode(['success'=>true,'message'=>"🌱 Semente plantada com sucesso!"]);

} catch (Exception $e) {
    sqlsrv_rollback($conn);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
