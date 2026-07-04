<?php
session_start();
include "db.php";
include "check_ban.php";

if (!isset($_SESSION['PlayerID'])) exit;

$playerID = $_SESSION['PlayerID'];
$itemID = $_POST['itemID'] ?? null;

if ($itemID && isset($_SESSION['inventario'][$itemID])) {
    $_SESSION['inventario'][$itemID]['lixo'] = true;
    echo json_encode(['status'=>'ok','itemID'=>$itemID]);
} else {
    echo json_encode(['status'=>'erro','mensagem'=>'Item inválido']);
}
