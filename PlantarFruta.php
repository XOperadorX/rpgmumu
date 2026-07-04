<?php
session_start();
include "db.php";

$playerID = $_SESSION['PlayerID'] ?? null;
$frutaID = intval($_POST['frutaID'] ?? 0);
$quantidade = intval($_POST['quantidade'] ?? 1);

if(!$playerID || !$frutaID){
    echo json_encode(['success'=>false,'message'=>"Dados inválidos"]);
    exit;
}

// Verifica se player tem sementes
$sql = "SELECT Quantidade FROM InventarioSementes WHERE PlayerID=? AND FrutaID=?";
$stmt = sqlsrv_query($conn, $sql, [$playerID,$frutaID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$row || $row['Quantidade'] < $quantidade){
    echo json_encode(['success'=>false,'message'=>"Sementes insuficientes"]);
    exit;
}

// Deduz sementes
$sql = "UPDATE InventarioSementes SET Quantidade=Quantidade-? WHERE PlayerID=? AND FrutaID=?";
sqlsrv_query($conn, $sql, [$quantidade,$playerID,$frutaID]);

// Planta na fazenda
$sql = "INSERT INTO Fazenda(PlayerID, FrutaID, Quantidade, PlantadoEm, Colhido)
        VALUES(?,?,?,GETDATE(),0)";
sqlsrv_query($conn, $sql, [$playerID,$frutaID,$quantidade]);

echo json_encode(['success'=>true,'message'=>"Plantado $quantidade semente(s)!"]);
?>
