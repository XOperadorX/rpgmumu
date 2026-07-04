<?php
session_start();
include "db.php";

$playerID = $_SESSION['PlayerID'] ?? null;
if(!$playerID){ echo json_encode([]); exit; }

$sql = "SELECT f.PlantioID, f.FrutaID, f.Quantidade, f.PlantadoEm, fr.TempoCrescimento
        FROM Fazenda f
        JOIN Frutas fr ON f.FrutaID = fr.FrutaID
        WHERE f.PlayerID=? AND f.Colhido=0";

$stmt = sqlsrv_query($conn, $sql, [$playerID]);
$plantios = [];
$agora = new DateTime();

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $plantadoEm = $row['PlantadoEm'] instanceof DateTime ? $row['PlantadoEm'] : new DateTime($row['PlantadoEm']);
    $tempoCrescimento = $row['TempoCrescimento']*60;
    $diff = $agora->getTimestamp() - $plantadoEm->getTimestamp();
    $tempoRestante = max($tempoCrescimento - $diff, 0);

    $plantios[] = [
        'PlantioID'=>$row['PlantioID'],
        'FrutaID'=>$row['FrutaID'],
        'Quantidade'=>$row['Quantidade'],
        'TempoRestante'=>$tempoRestante
    ];
}

echo json_encode($plantios);
?>
