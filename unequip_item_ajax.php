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
    echo json_encode(["msg"=>"Item inválido"]);
    exit;
}

// Verifica se o item pertence ao jogador
$stmt = sqlsrv_query($conn, "SELECT i.ItemID FROM Items i JOIN Characters c ON i.CharID = c.CharID WHERE i.ItemID=? AND c.PlayerID=?", [$itemID, $playerID]);
if(!$stmt || !sqlsrv_has_rows($stmt)){
    echo json_encode(["msg"=>"Item não encontrado"]);
    exit;
}

// Remove slot
sqlsrv_query($conn, "UPDATE Items SET Slot=NULL WHERE ItemID=?", [$itemID]);

echo json_encode(["msg"=>"Item retirado do equipamento!"]);
