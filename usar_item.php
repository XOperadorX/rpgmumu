<?php
session_start();
include "db.php";
header('Content-Type: application/json; charset=utf-8');

$playerID = $_SESSION['PlayerID'] ?? null;
$itemID   = $_POST['itemID'] ?? null;

if (!$playerID || !$itemID) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Item inválido.']);
    exit;
}

// Pega o item
$sqlItem = "SELECT * FROM [MumuDB].[dbo].[Items] WHERE ItemID = ? AND PlayerID = ?";
$stmtItem = sqlsrv_query($conn, $sqlItem, [$itemID, $playerID]);

if ($stmtItem === false || ($item = sqlsrv_fetch_array($stmtItem, SQLSRV_FETCH_ASSOC)) === null) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Item não encontrado.']);
    exit;
}

// Pega personagem
$sqlChar = "SELECT * FROM [MumuDB].[dbo].[Characters] WHERE PlayerID = ?";
$stmtChar = sqlsrv_query($conn, [$playerID]);
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);

// Pega efeito do item baseado na categoria
$sqlEffect = "SELECT * FROM [MumuDB].[dbo].[Items] WHERE Categoria = ?";
$stmtEffect = sqlsrv_query($conn, $sqlEffect, [$item['Categoria']]);
$effect = sqlsrv_fetch_array($stmtEffect, SQLSRV_FETCH_ASSOC);

if (!$effect) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Item sem efeito definido.']);
    exit;
}

// Aplica efeito
$mensagem = '';
switch ($effect['TipoEfeito']) {
    case 'HP':
        $novoHP = min($char['HP'] + $effect['Valor'], $char['MaxHP']);
        sqlsrv_query($conn, "UPDATE [MumuDB].[dbo].[Characters] SET HP = ? WHERE CharID = ?", [$novoHP, $char['CharID']]);
        $mensagem = "❤️ HP restaurado para $novoHP!";
        break;

    case 'Mana':
        $novoMana = min($char['Mana'] + $effect['Valor'], $char['MaxMana']);
        sqlsrv_query($conn, "UPDATE [MumuDB].[dbo].[Characters] SET Mana = ? WHERE CharID = ?", [$novoMana, $char['CharID']]);
        $mensagem = "🔵 Mana restaurada para $novoMana!";
        break;

    case 'BuffAtaque':
    case 'BuffDefesa':
    case 'BuffSpeed':
        // Para buffs temporários, você pode criar uma tabela Buffs:
        $sqlBuff = "INSERT INTO [MumuDB].[dbo].[Buffs] (CharID, Tipo, Valor, ExpiraEm) VALUES (?, ?, ?, DATEADD(SECOND, ?, GETDATE()))";
        sqlsrv_query($conn, $sqlBuff, [$char['CharID'], $effect['TipoEfeito'], $effect['Valor'], $effect['Duracao']]);
        $mensagem = "⚡ Buff {$effect['TipoEfeito']} ativado por {$effect['Duracao']} segundos!";
        break;

    default:
        $mensagem = "⚙️ Item usado, mas não teve efeito definido.";
        break;
}

// Remove 1 unidade do item
sqlsrv_query($conn, "UPDATE [MumuDB].[dbo].[Items] SET Quantidade = Quantidade - 1 WHERE ItemID = ? AND PlayerID = ?", [$itemID, $playerID]);
sqlsrv_query($conn, "DELETE FROM [MumuDB].[dbo].[Items] WHERE Quantidade <= 0 AND ItemID = ?", [$itemID]);

echo json_encode(['sucesso' => true, 'mensagem' => $mensagem]);
