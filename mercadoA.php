<?php
if (!isset($conn)) {
    include "db.php"; // Garante que a conexão está disponível
}

if (!isset($_SESSION)) {
    session_start();
}

$playerID = $_SESSION['PlayerID'] ?? null;

if ($playerID) {
    $stmt = sqlsrv_query($conn, "SELECT Banido FROM Players WHERE PlayerID = ?", [$playerID]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (!empty($row['Banido']) && $row['Banido'] == 1) {
            die("⛔ Você está banido e não pode acessar o jogo.");
        }
    }
}


// 🔹 Pega dados do jogador e inventário
$sql = "SELECT Username, MoedaMumu, CarteiraJSON FROM Players WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
if($stmt === false){ die(print_r(sqlsrv_errors(), true)); }
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

$username = $player['Username'];
$moedas = $player['MoedaMumu'];
$carteira = json_decode($player['CarteiraJSON'], true) ?: [];

// 🔹 Função para atualizar inventário e histórico
function atualizarInventario($conn, $playerID, $carteira){
    $json = json_encode($carteira);
    sqlsrv_query($conn, "UPDATE Players SET CarteiraJSON = ? WHERE PlayerID = ?", [$json, $playerID]);
}

// =====================================================================
// 🔹 VENDA DE ITEM
// =====================================================================
if(isset($_POST['vender'])){
    $itemID = intval($_POST['item_id']);
    $quantidade = intval($_POST['quantidade']);
    $preco = floatval($_POST['preco']);

    if($quantidade > 0 && $preco > 0 && isset($carteira[$itemID]) && $carteira[$itemID] >= $quantidade){
        // Remove do inventário
        $carteira[$itemID] -= $quantidade;
        if($carteira[$itemID] <= 0) unset($carteira[$itemID]);

        // Atualiza mercado
        $sql = "INSERT INTO Mercado (VendedorID, ItemID, Quantidade, PrecoMoedaMumu) VALUES (?, ?, ?, ?)";
        sqlsrv_query($conn, $sql, [$playerID, $itemID, $quantidade, $preco]);

        // Atualiza inventário
        atualizarInventario($conn, $playerID, $carteira);

        // Histórico
        $sql = "INSERT INTO HistoricoTransacoes (VendedorID, ItemID, Quantidade, PrecoMoedaMumu, Tipo) VALUES (?, ?, ?, ?, 'VENDA')";
        sqlsrv_query($conn, $sql, [$playerID, $itemID, $quantidade, $preco]);
    }
}

// =====================================================================
// 🔹 COMPRA DE ITEM
// =====================================================================
if(isset($_POST['comprar'])){
    $mercadoID = intval($_POST['mercado_id']);

    $sql = "SELECT * FROM Mercado WHERE MercadoID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$mercadoID]);
    $item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if(!$item) die("Item não encontrado.");

    $total = $item['PrecoMoedaMumu'];

    if($moedas >= $total){
        // Atualiza comprador
        $moedas -= $total;
        $carteira[$item['ItemID']] = ($carteira[$item['ItemID']] ?? 0) + $item['Quantidade'];
        $json = json_encode($carteira);
        sqlsrv_query($conn, "UPDATE Players SET MoedaMumu = ?, CarteiraJSON = ? WHERE PlayerID = ?", [$moedas, $json, $playerID]);

        // Atualiza vendedor
        sqlsrv_query($conn, "UPDATE Players SET MoedaMumu = MoedaMumu + ? WHERE PlayerID = ?", [$total, $item['VendedorID']]);

        // Histórico
        $sql = "INSERT INTO HistoricoTransacoes (CompradorID, VendedorID, ItemID, Quantidade, PrecoMoedaMumu, Tipo) VALUES (?, ?, ?, ?, ?, 'COMPRA')";
        sqlsrv_query($conn, $sql, [$playerID, $item['VendedorID'], $item['ItemID'], $item['Quantidade'], $total]);

        // Remove do mercado
        sqlsrv_query($conn, "DELETE FROM Mercado WHERE MercadoID = ?", [$mercadoID]);
    }
}

// =====================================================================
// 🔹 Puxa nomes dos itens do inventário
// =====================================================================
$itensNome = [];
if(count($carteira) > 0){
    $ids = implode(',', array_keys($carteira));
    $sql = "SELECT ItemID, Nome FROM Itens WHERE ItemID IN ($ids)";
    $stmt = sqlsrv_query($conn, $sql);
    if($stmt !== false){
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $itensNome[$row['ItemID']] = $row['Nome'];
        }
    }
}

// 🔹 Puxa itens do mercado
$sql = "SELECT m.MercadoID, m.ItemID, m.Quantidade, m.PrecoMoedaMumu, p.Username AS Vendedor, i.Nome AS ItemNome
        FROM Mercado m
        JOIN Players p ON m.VendedorID = p.PlayerID
        JOIN Itens i ON m.ItemID = i.ItemID
        ORDER BY m.MercadoID DESC";
$stmtMercado = sqlsrv_query($conn, $sql);

// 🔹 Puxa histórico
$sql = "SELECT h.Tipo, h.Quantidade, h.PrecoMoedaMumu, i.Nome AS ItemNome, 
               c.Username AS Comprador, v.Username AS Vendedor, h.DataTransacao
        FROM HistoricoTransacoes h
        LEFT JOIN Players c ON h.CompradorID = c.PlayerID
        LEFT JOIN Players v ON h.VendedorID = v.PlayerID
        JOIN Itens i ON h.ItemID = i.ItemID
        ORDER BY h.HistoricoID DESC";
$stmtHistorico = sqlsrv_query($conn, $sql);

include "inventario_template.php"; // Carrega o HTML com inventário, mercado e histórico
