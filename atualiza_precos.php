<?php
if(!isset($conn)) include "db.php";
if(!isset($_SESSION)) session_start();

header('Content-Type: application/json');

$stmt = sqlsrv_query($conn, "SELECT AtivoID, Nome, PrecoAtual FROM Ativos");
$ativos = [];
$grafico = [];

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $ativoID = $row['AtivoID'];
    $nome = $row['Nome'];
    $precoAtual = floatval($row['PrecoAtual']);
    $variacao = mt_rand(-5,5); // -5% a +5%
    $novoPreco = max(1, round($precoAtual + ($precoAtual * $variacao / 100), 2));

    sqlsrv_query($conn,"UPDATE Ativos SET PrecoAtual=?, UltimaVariacao=? WHERE AtivoID=?", [$novoPreco, $variacao, $ativoID]);

    $grafico[$nome][] = ['preco' => $novoPreco];
}

echo json_encode([
    'success'=>true,
    'grafico'=>$grafico
]);
