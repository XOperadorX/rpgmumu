<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado.");
}

$playerID = $_SESSION['PlayerID'];
$charID = isset($_GET['CharID']) && $_GET['CharID'] !== '' ? $_GET['CharID'] : null;
$itemType = isset($_GET['ItemType']) && $_GET['ItemType'] !== '' ? $_GET['ItemType'] : null;

$sql = "SELECT dl.*, c.Name 
        FROM DungeonLog dl
        JOIN Characters c ON dl.CharID = c.CharID
        WHERE c.PlayerID = ?";
$params = [$playerID];

if($charID){
    $sql .= " AND dl.CharID = ?";
    $params[] = $charID;
}

if($itemType){
    $sql .= " AND dl.Item LIKE ?";
    $params[] = '%' . $itemType . '%';
}

$sql .= " ORDER BY dl.DataHora DESC";

$stmt = sqlsrv_query($conn, $sql, $params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=dungeon_log.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['DataHora', 'Personagem', 'XP', 'Item']);

if($stmt && sqlsrv_has_rows($stmt)){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $dataHora = $row['DataHora'] ? $row['DataHora']->format('d/m/Y H:i:s') : '';
        fputcsv($output, [$dataHora, $row['Name'], $row['XP'], $row['Item']]);
    }
}
fclose($output);
exit;
?>
