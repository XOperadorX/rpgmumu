<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// Saldo de moedas (Players)
$stmtSaldo = sqlsrv_query($conn, "SELECT MoedaMumu FROM dbo.Players WHERE PlayerID = ?", [$playerID]);
if($stmtSaldo === false){
    echo json_encode(['erro' => 'Erro ao buscar saldo.']);
    exit;
}
$rowSaldo = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC);
$saldo = intval($rowSaldo['MoedaMumu'] ?? 0);

// Histórico de vendas (histvend)
$stmtHist = sqlsrv_query($conn, "
    SELECT TOP 50 Acao, Data
    FROM dbo.histvend
    WHERE PlayerID = ?
    ORDER BY ID DESC
", [$playerID]);
if($stmtHist === false){
    echo json_encode(['erro' => 'Erro ao buscar histórico.']);
    exit;
}

$html = '<tr><th>Data</th><th>Ação</th></tr>';
while($row = sqlsrv_fetch_array($stmtHist, SQLSRV_FETCH_ASSOC)){
    $data = $row['Data']->format('d/m/Y H:i');
    $acao = htmlspecialchars($row['Acao']);
    $html .= "<tr><td>{$data}</td><td>{$acao}</td></tr>";
}

echo json_encode([
    'saldo' => $saldo,
    'html' => $html
]);
