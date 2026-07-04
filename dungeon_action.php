<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: application/json');

// ====================================
// Verificações básicas
// ====================================
if (!isset($_SESSION['PlayerID']) || !isset($_SESSION['CharID'])) {
    echo json_encode(['erro' => 'Sessão inválida.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$charID = $_SESSION['CharID'];

// ====================================
// Sistema de DROP de Itens
// ====================================
// Cria um array de possíveis itens dropáveis
$itensPossiveis = [
    ['Nome' => 'Espada de Ferro', 'Chance' => 30],
    ['Nome' => 'Poção de Cura', 'Chance' => 50],
    ['Nome' => 'Escudo de Bronze', 'Chance' => 20],
    ['Nome' => 'Anel Mágico', 'Chance' => 10],
    ['Nome' => 'Cristal Azul', 'Chance' => 5],
];

// Quantidade de drops possíveis por monstro
$qtdDrops = rand(1, 2);
$itensDropados = [];

for ($i = 0; $i < $qtdDrops; $i++) {
    foreach ($itensPossiveis as $item) {
        if (rand(1, 100) <= $item['Chance']) {
            $nome = $item['Nome'];

            // Verifica se o item já existe no inventário do jogador
            $stmt = sqlsrv_query($conn, "SELECT ItemID, Quantidade FROM dbo.Items WHERE Nome = ? AND PlayerID = ? AND CharID = ?", [$nome, $playerID, $charID]);
            if ($stmt && $existente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $novaQtd = $existente['Quantidade'] + 1;
                sqlsrv_query($conn, "UPDATE dbo.Items SET Quantidade = ? WHERE ItemID = ?", [$novaQtd, $existente['ItemID']]);
            } else {
                sqlsrv_query($conn, "INSERT INTO dbo.Items (CharID, PlayerID, Nome, Quantidade) VALUES (?, ?, ?, 1)", [$charID, $playerID, $nome]);
            }

            // Guarda para resposta
            $itensDropados[] = $nome;
        }
    }
}

// ====================================
// Atualiza moedas e histórico
// ====================================
$moedasGanhas = rand(100, 500);
sqlsrv_query($conn, "UPDATE dbo.Players SET MoedaMumu = MoedaMumu + ? WHERE PlayerID = ?", [$moedasGanhas, $playerID]);
sqlsrv_query($conn, "INSERT INTO dbo.Historico (PlayerID, Descricao, Valor, Tipo, Data) VALUES (?, ?, ?, 'Dungeon', GETDATE())",
             [$playerID, 'Recompensa de dungeon (+'.$moedasGanhas.' MoedaMumu)', $moedasGanhas]);

// ====================================
// Resposta JSON (para atualizar a tela)
// ====================================
echo json_encode([
    'status' => 'ok',
    'moedas' => $moedasGanhas,
    'itens' => $itensDropados
]);
exit;
?>
