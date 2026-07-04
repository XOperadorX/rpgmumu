<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['error'=>'Acesso negado']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

$hist = sqlsrv_query($conn, "SELECT TOP 10 * FROM Historico WHERE PlayerID=? ORDER BY CreatedAt DESC", [$playerID]);
$dados = [];
while($h = sqlsrv_fetch_array($hist, SQLSRV_FETCH_ASSOC)){
    $dados[] = [
        'datahora' => $h['CreatedAt']->format('d/m/Y H:i'),
        'acao' => ucfirst($h['Acao']),
        'item' => $h['Item'],
        'qtd' => $h['MoedaChange']
    ];
}

echo json_encode($dados);
