<?php
include "db.php";

header('Content-Type: application/json; charset=utf-8');

$charid = isset($_GET['charid']) ? intval($_GET['charid']) : 0;
if ($charid <= 0) {
    echo json_encode(null);
    exit;
}

$sql = "SELECT TOP 1 Level, HP, MaxHP, Mana, MaxMana, Power, Exp
        FROM dbo.Characters WHERE CharID = ?";
$stmt = sqlsrv_query($conn, $sql, [$charid]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo json_encode($row);
?>
