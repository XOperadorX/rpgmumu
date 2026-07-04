<?php
session_start();
include "db.php";

$playerID = $_SESSION['PlayerID'];
$frutaID = intval($_POST['frutaID']);
$quantidade = intval($_POST['quantidade']);

if($quantidade <= 0){
    echo json_encode(['success'=>false,'message'=>"Quantidade inválida"]);
    exit;
}

// Inserir plantio
$sql = "INSERT INTO Fazenda (PlayerID, FrutaID, Quantidade, PlantadoEm)
        VALUES (?, ?, ?, GETDATE())";
$stmt = sqlsrv_query($conn, $sql, [$playerID, $frutaID, $quantidade]);

if($stmt){
    echo json_encode(['success'=>true, 'message'=>"Planta(s) plantada(s)!"]);
} else {
    echo json_encode(['success'=>false, 'message'=>"Erro ao plantar."]);
}
?>
