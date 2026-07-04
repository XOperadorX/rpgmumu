<?php
session_start();
include "db.php"; // conexão SQL Server

// Aqui você pode ter uma tabela que guarda o preço atual da moeda
// Supondo que exista [MumuDB].[dbo].[Currency] com coluna PrecoAtual
$sql = "SELECT TOP 1 [PrecoAtual] FROM [MumuDB].[dbo].[Currency] ORDER BY ID DESC";
$stmt = sqlsrv_query($conn, $sql);
if($stmt === false){
    die(json_encode(['error' => 'Erro ao buscar preço']));
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$preco = floatval($row['PrecoAtual'] ?? 10.00);

echo json_encode(['preco' => $preco]);
