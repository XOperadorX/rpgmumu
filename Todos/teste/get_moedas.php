<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) exit;

$playerID = $_SESSION['PlayerID'];
$sql = "SELECT MoedaMumu FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, array($playerID));
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo $row['MoedaMumu'];
