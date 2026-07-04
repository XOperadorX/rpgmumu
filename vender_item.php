<?php
session_start();
include "db.php";
header('Content-Type: application/json; charset=utf-8');

$playerID = $_SESSION['PlayerID'] ?? null;
$itemID   = $_POST['itemID'] ?? null;
$valor    = intval($_POST['valor'] ?? 0);
$nome     = $_POST['nome'] ?? '';

if (!$playerID || !$itemID) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Item inválido.']);
    exit;
}

// Exemplo: deleta o item do inventário
$sql = "DELETE FROM [MumuDB].[dbo].[Items] WHERE ItemID = ? AND PlayerID = ?";
$params = [$itemID, $playerID];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao vender item.']);
    exit;
}

// Aqui você pode atualizar o ouro do jogador:
$sql2 = "UPDATE [MumuDB].[dbo].[Players] SET Gold = Gold + ? WHERE PlayerID = ?";
sqlsrv_query($conn, $sql2, [$valor, $playerID]);

echo json_encode(['sucesso' => true, 'mensagem' => "💰 Item '$nome' vendido! +$valor moedas"]);
?>
