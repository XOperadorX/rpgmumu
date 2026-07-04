<?php
session_start();
include "db.php"; // conexão SQL Server

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado.");
}

$playerID = $_SESSION['PlayerID'];

// Busca histórico de compras e vendas do jogador
$sql = "
SELECT t.TransacaoID, a.Nome AS Ativo, t.Tipo, t.Quantidade, t.PrecoUnitario, t.DataHora
FROM dbo.Transacoes t
JOIN dbo.Ativos a ON t.AtivoID = a.AtivoID
WHERE t.PlayerID = ?
ORDER BY t.DataHora DESC
";

$params = [$playerID];
$stmt = sqlsrv_query($conn, $sql, $params);

if(!$stmt){
    die(print_r(sqlsrv_errors(), true));
}

// Monta o array de histórico
$historico = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $historico[] = $row;
}

// Retorna em JSON
header('Content-Type: application/json');
echo json_encode($historico, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
