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

$itemID = $_POST['ItemID'] ?? null;

if(!$itemID){
    echo json_encode(["msg" => "Item inválido."]);
    exit;
}

// Pega nome do item
$stmt = sqlsrv_query($conn, "SELECT Name FROM Items WHERE ItemID=? AND CharID IN (SELECT CharID FROM Characters WHERE PlayerID=?)", [$itemID, $playerID]);
$item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$item){
    echo json_encode(["msg" => "Item não encontrado."]);
    exit;
}

// Remove item
sqlsrv_query($conn, "DELETE FROM Items WHERE ItemID=? AND CharID IN (SELECT CharID FROM Characters WHERE PlayerID=?)", [$itemID, $playerID]);

// Dá moedas (exemplo: 1 moedas por item vendido)
sqlsrv_query($conn, "UPDATE Players SET MoedaMumu = MoedaMumu + 1 WHERE PlayerID=?", [$playerID]);

// Pega saldo atualizado
$stmtMoedas = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
$moedaRow = sqlsrv_fetch_array($stmtMoedas, SQLSRV_FETCH_ASSOC);
$saldo = $moedaRow['MoedaMumu'] ?? 0;

echo json_encode([
    "msg" => "Item '{$item['Name']}' vendido! Você ganhou +1 Moedas Mumu 💰",
    "moedas" => $saldo
]);
