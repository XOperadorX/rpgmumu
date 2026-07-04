<?php
session_start();
include "db.php";

$playerID = $_SESSION['PlayerID'] ?? null;
if(!$playerID){ echo json_encode([]); exit; }

$sql = "SELECT FrutaID, Quantidade FROM InventarioFrutas WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
$frutas = [];

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $frutas[] = $row;
}

echo json_encode($frutas);
?>
