<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['success'=>false,'message'=>"⛔ Faça login primeiro."]);
    exit;
}

$playerID = $_SESSION['PlayerID'];

$sql = "SELECT f.Nome, i.FrutaID, i.Quantidade 
        FROM dbo.InventarioFrutas i
        JOIN dbo.Frutas f ON i.FrutaID = f.FrutaID
        WHERE i.PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);

$sementes = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $sementes[] = $row;
}

echo json_encode(['success'=>true,'inventario'=>$sementes]);
