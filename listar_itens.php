<?php
// ==========================
// listar_itens.php
// ==========================
session_start();
include "db.php"; // <-- aqui você faz sua conexão SQLSRV
header('Content-Type: application/json; charset=utf-8');

// Garante que o jogador está logado
if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado. Faça login.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// ==========================
// Consulta SQL
// ==========================
$sql = "
SELECT TOP 1000
    ItemID,
    CharID,
    Nome,
    PlayerID,
    Quantidade,
    Descricao,
    DataAdquirido,
    UsadoPor,
    PodeUsar,
    PodeMarcarLixo,
    PodeEnviarArmazem,
    PodeSoltar,
    Raridade,
    Valor,
    Categoria
FROM [MumuDB].[dbo].[Items]
WHERE PlayerID = ?
ORDER BY DataAdquirido DESC
";

// ==========================
// Executa consulta com segurança (prepared statement)
// ==========================
$params = [$playerID];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['sucesso' => false, 'erro' => sqlsrv_errors()]);
    exit;
}

// ==========================
// Monta array de resultados
// ==========================
$itens = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Converte DataAdquirido (se for objeto DateTime)
    if ($row['DataAdquirido'] instanceof DateTime) {
        $row['DataAdquirido'] = $row['DataAdquirido']->format('Y-m-d H:i:s');
    }
    $itens[] = $row;
}

echo json_encode(['sucesso' => true, 'itens' => $itens], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
