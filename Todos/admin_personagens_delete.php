<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID']) || $_SESSION['Role'] !== 'admin'){
    die("Acesso negado.");
}

$CharID = $_GET['CharID'];

// Deleta personagem
$sql = "DELETE FROM Characters WHERE CharID=?";
$stmt = sqlsrv_query($conn, $sql, [$CharID]);

if($stmt){
    header("Location: admin_personagens.php?deleted=1");
}else{
    die(print_r(sqlsrv_errors(), true));
}
