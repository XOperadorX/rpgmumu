<?php
session_start();
include "db.php";

$playerID = $_SESSION['PlayerID'];
$frutaID = intval($_POST['frutaID']);
$quantidade = intval($_POST['quantidade']);
$tipo = $_POST['tipo']; // 'venda' ou 'compra'
$preco = intval($_POST['preco']);

if(!in_array($tipo, ['venda','compra']) || $quantidade <= 0 || $preco <= 0){
    echo json_encode(['success'=>false,'message'=>"Dados inválidos"]);
    exit;
}

// Inserir no mercado
$sql = "INSERT INTO MercadoFrutas (PlayerID, FrutaID, Quantidade, Preco, Tipo) VALUES (?, ?, ?, ?, ?)";
$stmt = sqlsrv_query($conn, $sql, [$playerID, $frutaID, $quantidade, $preco, $tipo]);

echo json_encode($stmt ? ['success'=>true,'message'=>"Operação registrada!"] : ['success'=>false,'message'=>"Erro no mercado"]);
?>
