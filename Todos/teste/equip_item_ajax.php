<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) exit;

$playerID = $_SESSION['PlayerID'];
$itemID = intval($_POST['itemID']);

// Puxar tipo do item
$sql = "SELECT Tipo FROM Inventory WHERE PlayerID=? AND ItemID=?";
$stmt = sqlsrv_query($conn, $sql, array($playerID, $itemID));
$item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$slot = $item['Tipo'];

// Atualizar equipamento
$update = "UPDATE Equipment SET ItemID=? WHERE PlayerID=? AND Slot=?";
sqlsrv_query($conn, $update, array($itemID, $playerID, $slot));

echo "ok";
