<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['success' => false, 'message' => "⛔ Faça login primeiro."]);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$frutaID = $_POST['frutaID'] ?? null;

if (!$frutaID) {
    echo json_encode(['success' => false, 'message' => "Selecione uma semente para comprar."]);
    exit;
}

sqlsrv_begin_transaction($conn);

try {
    // Preço da semente
    $sql = "SELECT PrecoSemente FROM dbo.Frutas WHERE FrutaID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$frutaID]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$row) throw new Exception("❌ Fruta não encontrada.");
    $preco = $row['PrecoSemente'];

    // MoedaMumu do jogador
    $sql2 = "SELECT MoedaMumu FROM Players WHERE PlayerID = ?";
    $stmt2 = sqlsrv_query($conn, $sql2, [$playerID]);
    $row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
    if (!$row2 || $row2['MoedaMumu'] < $preco) throw new Exception("💰 MoedaMumu insuficiente!");

    // Deduz MoedaMumu
    sqlsrv_query($conn, "UPDATE Players SET MoedaMumu = MoedaMumu - ? WHERE PlayerID = ?", [$preco, $playerID]);

    // Atualiza ou insere semente
    $sqlCheck = "SELECT Quantidade FROM dbo.InventarioFrutas WHERE PlayerID = ? AND FrutaID = ?";
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, [$playerID, $frutaID]);
    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

    if ($rowCheck) {
        sqlsrv_query($conn, "UPDATE dbo.InventarioFrutas SET Quantidade = Quantidade + 1 WHERE PlayerID = ? AND FrutaID = ?", [$playerID, $frutaID]);
    } else {
        sqlsrv_query($conn, "INSERT INTO dbo.InventarioFrutas (PlayerID, FrutaID, Quantidade) VALUES (?, ?, 1)", [$playerID, $frutaID]);
    }

    sqlsrv_commit($conn);

    echo json_encode(['success'=>true, 'message'=>"✅ Semente comprada com sucesso!"]);

} catch (Exception $e) {
    sqlsrv_rollback($conn);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
