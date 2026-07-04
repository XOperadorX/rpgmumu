<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['error'=>'Acesso negado']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// Buscando saldos do jogador
$stmt = $conn->prepare("SELECT MoedaMumu, Corrente, Poupanca, Pix, Real FROM Players WHERE PlayerID = ?");
$stmt->execute([$playerID]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$player){
    echo json_encode(['error'=>'Jogador não encontrado']);
    exit;
}

// Histórico
$stmtHist = $conn->prepare("SELECT TOP 10 Tipo, Valor, Data FROM BankHistory WHERE PlayerID=? ORDER BY Data DESC");
$stmtHist->execute([$playerID]);
$history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

// Retorna JSON
echo json_encode([
    'saldo' => $player['MoedaMumu'] ?? 0,
    'corrente' => $player['Corrente'] ?? 0,
    'poupanca' => $player['Poupanca'] ?? 0,
    'pix' => $player['Pix'] ?? 0,
    'real' => $player['Real'] ?? 0,
    'historico' => $history
]);
