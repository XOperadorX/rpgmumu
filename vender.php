<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['success'=>false, 'message'=>'⛔ Faça login']);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$tipo = $_POST['tipo'] ?? '';
$frutaID = intval($_POST['frutaID'] ?? 0);
$quantidade = intval($_POST['quantidade'] ?? 0);

if(!$tipo || !$frutaID || $quantidade <= 0){
    echo json_encode(['success'=>false, 'message'=>'Dados inválidos']);
    exit;
}

// Função para registrar histórico
function registrarHistorico($conn, $playerID, $acao, $nomeFruta, $quantidade){
    $sql = "INSERT INTO dbo.HistoricoFazenda (PlayerID, Acao, NomeFruta, Quantidade, DataRegistro) 
            VALUES (?, ?, ?, ?, GETDATE())";
    sqlsrv_query($conn, $sql, [$playerID, $acao, $nomeFruta, $quantidade]);
}

// Buscar preço da fruta
$sql = "SELECT Nome, PrecoVenda FROM dbo.Frutas WHERE FrutaID=?";
$stmt = sqlsrv_query($conn, $sql, [$frutaID]);
$fruta = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$fruta){
    echo json_encode(['success'=>false, 'message'=>'Fruta não encontrada']);
    exit;
}

// Verificar estoque
if($tipo == 'semente'){
    $sql = "SELECT Quantidade FROM dbo.Sementes WHERE PlayerID=? AND FrutaID=?";
} else {
    $sql = "SELECT Quantidade FROM dbo.InventarioFrutas WHERE PlayerID=? AND FrutaID=?";
}

$stmt = sqlsrv_query($conn, $sql, [$playerID, $frutaID]);
$item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$item || $item['Quantidade'] < $quantidade){
    echo json_encode(['success'=>false, 'message'=>'Quantidade insuficiente']);
    exit;
}

// Atualizar estoque
$novaQtd = $item['Quantidade'] - $quantidade;
if($tipo == 'semente'){
    $sql = "UPDATE dbo.Sementes SET Quantidade=? WHERE PlayerID=? AND FrutaID=?";
} else {
    $sql = "UPDATE dbo.InventarioFrutas SET Quantidade=? WHERE PlayerID=? AND FrutaID=?";
}
sqlsrv_query($conn, $sql, [$novaQtd, $playerID, $frutaID]);

// Atualizar saldo
$valorTotal = $quantidade * $fruta['PrecoVenda'];
sqlsrv_query($conn, "UPDATE dbo.BankAccounts SET Poupanca = Poupanca + ? WHERE PlayerID=?", [$valorTotal, $playerID]);

// Registrar histórico
registrarHistorico($conn, $playerID, 'Venda', $fruta['Nome'], $quantidade);

echo json_encode(['success'=>true, 'message'=>"✅ Vendido $quantidade x {$fruta['Nome']} por 💎 $valorTotal"]);
