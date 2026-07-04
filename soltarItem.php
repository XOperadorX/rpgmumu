<?php
session_start();
include "db.php";
include "check_ban.php";

if (!isset($_SESSION['PlayerID'])) exit;

$playerID = $_SESSION['PlayerID'];
$itemID = $_POST['itemID'] ?? null;

if ($itemID && isset($_SESSION['inventario'][$itemID])) {

    // Remove do inventário da sessão
    unset($_SESSION['inventario'][$itemID]);

    // Remove do banco de dados
    $sql = "DELETE FROM Inventario WHERE PlayerID = ? AND ItemID = ?";
    $params = [$playerID, $itemID];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['status'=>'erro','mensagem'=>'Falha ao deletar do banco']);
        exit;
    }

    echo json_encode(['status'=>'ok','itemID'=>$itemID]);

} else {
    echo json_encode(['status'=>'erro','mensagem'=>'Item inválido']);
}
