<?php
session_start();
include "db.php";
if(!isset($_SESSION['PlayerID'])) exit;
$playerID = $_SESSION['PlayerID'];
$charID = intval($_POST['charid']);
$x = intval($_POST['x']);
$y = intval($_POST['y']);
sqlsrv_query($conn, "UPDATE CharacterPositions SET Xpos=?, Ypos=? WHERE PlayerID=? AND CharID=?", [$x, $y, $playerID, $charID]);
?>
