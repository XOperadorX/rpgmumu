<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) exit;

$playerID = $_SESSION['PlayerID'];
$inimigo = $_POST['inimigo'];
$dano = intval($_POST['dano']);

$sql = "INSERT INTO DungeonLogs (PlayerID, Inimigo, Dano, DataHora) VALUES (?, ?, ?, GETDATE())";
sqlsrv_query($conn, $sql, array($playerID, $inimigo, $dano));
echo "ok";
