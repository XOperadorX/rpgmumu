<?php
header('Content-Type: application/json');

if (!isset($conn)) include "db.php";
if (!isset($_SESSION)) session_start();

$playerID = $_SESSION['PlayerID'] ?? null;
if (!$playerID) {
    echo json_encode(['success'=>false, 'mensagem'=>'⛔ Faça login primeiro!']);
    exit;
}

// Lê JSON enviado via fetch
$data = json_decode(file_get_contents('php://input'), true);
$charID = intval($data['CharID'] ?? 0);
$HP     = intval($data['HP'] ?? 0);
$Mana   = intval($data['Mana'] ?? 0);
$Power  = intval($data['Power'] ?? 0);

if ($charID <= 0) {
    echo json_encode(['success'=>false, 'mensagem'=>'ID de personagem inválido']);
    exit;
}

// Verifica se o personagem pertence ao jogador
$stmtCheck = sqlsrv_query($conn, "SELECT CharID FROM Characters WHERE CharID=? AND PlayerID=?", [$charID, $playerID]);
if (!$stmtCheck || sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC) === null) {
    echo json_encode(['success'=>false, 'mensagem'=>'⛔ Personagem não encontrado ou não pertence a você.']);
    exit;
}

// Atualiza os atributos
$sqlUpdate = "UPDATE Characters SET HP=?, Mana=?, Power=? WHERE CharID=?";
$params = [$HP, $Mana, $Power, $charID];
$stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $params);

if ($stmtUpdate === false) {
    echo json_encode(['success'=>false, 'mensagem'=>'❌ Erro ao atualizar atributos.']);
    exit;
}

echo json_encode(['success'=>true, 'mensagem'=>'✅ Atributos atualizados com sucesso!']);
