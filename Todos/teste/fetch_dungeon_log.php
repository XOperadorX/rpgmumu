<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) exit;

$playerID = $_SESSION['PlayerID'];
$sql = "SELECT TOP 10 Inimigo, Dano, DataHora FROM DungeonLogs WHERE PlayerID=? ORDER BY DataHora DESC";
$stmt = sqlsrv_query($conn, $sql, array($playerID));

echo "<h3>Últimos logs da dungeon</h3><ul>";
while($log = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    echo "<li>{$log['DataHora']}: {$log['Inimigo']} - Dano: {$log['Dano']}</li>";
}
echo "</ul>";
