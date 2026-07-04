<?php
session_start();
include "db.php";
include "check_ban.php";

if (!isset($_SESSION['PlayerID'])) exit;

$playerID = $_SESSION['PlayerID'];
$itemID = $_POST['itemID'] ?? null;

if (!$itemID) { echo json_encode(['status'=>'erro']); exit; }

// Aqui você pode adicionar lógica para mover item para tabela de armazém
// Exemplo: apenas remove do inventário da sessão
if (isset($_SESSION['inventario'][$itemID])) unset($_SESSION['inventario'][$itemID]);

echo json_encode(['status'=>'ok','itemID'=>$itemID]);
