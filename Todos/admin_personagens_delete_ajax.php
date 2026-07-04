<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID']) || $_SESSION['Role'] !== 'admin'){
    die(json_encode(['success'=>false]));
}

$CharID = $_GET['CharID'] ?? 0;

$stmt = sqlsrv_query($conn, "DELETE FROM Characters WHERE CharID=?", [$CharID]);

if($stmt){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false]);
}
