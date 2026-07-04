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

$daysRequested = isset($_POST['days']) ? (int)$_POST['days'] : 1;
$costPerDay = 2000; // custo por dia

// Pega saldo atual
$sql = "SELECT MoedaMumu, LastLoginTime FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$player){
    echo json_encode(['error'=>'Jogador não encontrado']);
    exit;
}

// Converte LastLoginTime para DateTime
$lastLogin = $player['LastLoginTime'] instanceof DateTime ? $player['LastLoginTime'] : new DateTime($player['LastLoginTime']);
$now = new DateTime();

// Define limite máximo (30 dias à frente da data atual)
$maxLimit = (clone $now)->modify('+30 days');

// Calcula quantos dias ainda podem ser comprados
$remainingInterval = $lastLogin < $maxLimit ? $lastLogin->diff($maxLimit) : 0;
$remainingDays = $remainingInterval ? (int)$remainingInterval->days : 0;

// Se já atingiu o limite
if($remainingDays <= 0){
    echo json_encode([
        'msg' => 'Seu tempo já está completo! Não é possível comprar mais dias.',
        'remainingDays' => 0
    ]);
    exit;
}

// Ajusta dias a serem comprados caso ultrapassem o limite
$daysToAdd = min($daysRequested, $remainingDays);
$totalCost = $daysToAdd * $costPerDay;

// Verifica saldo
if($player['MoedaMumu'] < $totalCost){
    echo json_encode([
        'msg' => 'Saldo insuficiente!',
        'remainingDays' => $remainingDays
    ]);
    exit;
}

// Atualiza LastLoginTime
$newLastLogin = clone $lastLogin;
$newLastLogin->modify("+$daysToAdd days");
$newMoeda = $player['MoedaMumu'] - $totalCost;

$sqlUpdate = "UPDATE Players SET MoedaMumu=?, LastLoginTime=? WHERE PlayerID=?";
sqlsrv_query($conn, $sqlUpdate, [$newMoeda, $newLastLogin, $playerID]);

// Calcula dias restantes após a compra
$remainingAfterPurchase = $newLastLogin < $maxLimit ? $newLastLogin->diff($maxLimit)->days : 0;

echo json_encode([
    'msg' => "Você comprou $daysToAdd dia(s) com sucesso!",
    'remainingDays' => $remainingAfterPurchase,
    'newMoeda' => $newMoeda
]);
