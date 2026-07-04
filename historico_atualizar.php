<?php
//historico_atualizar.php
session_start();
include "db.php";
header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// ==========================
// Busca saldo atual
// ==========================
$stmtSaldo = sqlsrv_query($conn, "SELECT MoedasMumu FROM dbo.Characters WHERE PlayerID = ?", [$playerID]);
$rowSaldo = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC);
$saldo = intval($rowSaldo['MoedasMumu'] ?? 0);

// ==========================
// Busca histórico recente
// ==========================
$stmt = sqlsrv_query($conn, "
    SELECT TOP 50 Acao, Data
    FROM dbo.Historico
    WHERE PlayerID = ?
    ORDER BY ID DESC
", [$playerID]);

$html = '<tr><th>Data</th><th>Ação</th></tr>';
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $data = $row['Data']->format('d/m/Y H:i');
    $acao = htmlspecialchars($row['Acao']);
    $html .= "<tr><td>{$data}</td><td>{$acao}</td></tr>";
}

echo json_encode([
    'saldo' => $saldo,
    'html' => $html
]);
