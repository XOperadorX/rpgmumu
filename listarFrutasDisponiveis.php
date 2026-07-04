<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT FrutaID, Nome, PrecoSemente FROM dbo.Frutas ORDER BY Nome";
$stmt = sqlsrv_query($conn, $sql);

$frutas = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $frutas[] = [
        'FrutaID' => $row['FrutaID'],
        'Nome' => $row['Nome'],
        'PrecoSemente' => $row['PrecoSemente']
    ];
}

echo json_encode($frutas);
