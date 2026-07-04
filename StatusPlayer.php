<?php
session_start();
include "db.php";

$playerID = $_SESSION['PlayerID'] ?? null;
if(!$playerID){
    echo json_encode([]);
    exit;
}

$sql = "SELECT MoedaMumu, Nivel, XP FROM Players WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
$data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo json_encode($data);
?>
