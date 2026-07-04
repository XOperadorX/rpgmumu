<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("0");
}

$playerID = $_SESSION['PlayerID'];

$sql = "SELECT MoedaMumu FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);

if($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    echo $row['MoedaMumu'];
} else {
    echo "0";
}


