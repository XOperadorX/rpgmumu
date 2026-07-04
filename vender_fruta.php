<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['success'=>false,'message'=>'⛔ Faça login primeiro.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$frutaID = intval($_POST['frutaID']);
$qtd = intval($_POST['quantidade']);
$preco = intval($_POST['preco']);

// Verifica inventário
$sql = "SELECT Quantidade FROM InventarioFrutas WHERE PlayerID = ? AND FrutaID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID, $frutaID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$row || $row['Quantidade'] < $qtd){
    echo json_encode(['success'=>false,'message'=>'❌ Quantidade insuficiente.']);
    exit;
}

$valorTotal = $qtd * $preco;

// Atualiza inventário
sqlsrv_query($conn, "UPDATE InventarioFrutas SET Quantidade = Quantidade - ? WHERE PlayerID = ? AND FrutaID = ?", [$qtd, $playerID, $frutaID]);

// Credita moedas
sqlsrv_query($conn, "UPDATE Players SET MoedaMumu = MoedaMumu + ? WHERE PlayerID = ?", [$valorTotal, $playerID]);

echo json_encode(['success'=>true,'message'=>"💰 Você vendeu $qtd frutas por $valorTotal MoedaMumu!"]);
?>
