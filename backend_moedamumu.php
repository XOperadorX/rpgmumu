<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['error'=>'Não logado']);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$daysToBuy = isset($_POST['days']) ? (int)$_POST['days'] : 0;

// ===== Pega dados do jogador =====
$sql = "SELECT MoedaMumu, LastLoginTime FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$player){
    echo json_encode(['error'=>'Jogador não encontrado']);
    exit;
}

// Nível do personagem
$level = $_SESSION['char']['Level'] ?? 1;
$costPerDay = 1000 + ($level - 1) * 50;

// ===== Calcula juros compostos desde o último login =====
$now = new DateTime();
$lastUpdate = $player['LastLoginTime'] instanceof DateTime ? $player['LastLoginTime'] : new DateTime($player['LastLoginTime']);
$diffSeconds = max(0, $now->getTimestamp() - $lastUpdate->getTimestamp());

$moedaMumu = $player['MoedaMumu'];
$interestRateDaily = 0.05;
$interestRatePerSecond = pow(1 + $interestRateDaily, 1/86400) - 1;

if($moedaMumu > 0 && $diffSeconds > 0){
    $moedaMumu = floor($moedaMumu * pow(1 + $interestRatePerSecond, $diffSeconds));
}

// ===== Se comprou dias, subtrai MoedaMumu e atualiza LastLoginTime =====
$totalCost = $daysToBuy * $costPerDay;

if($daysToBuy > 0){
    if($moedaMumu < $totalCost){
        echo json_encode(['msg'=>'Saldo insuficiente!', 'MoedaMumu'=>$moedaMumu]);
        exit;
    }
    $moedaMumu -= $totalCost;
    $lastUpdate->modify("+$daysToBuy days");
}

// Atualiza Players
$sqlUpdate = "UPDATE Players SET MoedaMumu=?, LastLoginTime=? WHERE PlayerID=?";
sqlsrv_query($conn, $sqlUpdate, [$moedaMumu, $lastUpdate, $playerID]);

// Atualiza HP/Mana/Power
$sqlChar = "UPDATE Characters 
            SET HP = MaxHP, Mana = MaxMana, Power = MaxPower
            WHERE PlayerID=?";
sqlsrv_query($conn, $sqlChar, [$playerID]);

if(isset($_SESSION['char'])){
    $_SESSION['char']['HP'] = $_SESSION['char']['MaxHP'];
    $_SESSION['char']['Mana'] = $_SESSION['char']['MaxMana'];
    $_SESSION['char']['Power'] = $_SESSION['char']['MaxPower'];
}

// ===== Retorna JSON =====
echo json_encode([
    'MoedaMumu' => $moedaMumu,
    'char' => $_SESSION['char'] ?? null,
    'costPerDay' => $costPerDay,
    'interestRateDaily' => $interestRateDaily,
    'lastUpdateTimestamp' => $lastUpdate->getTimestamp()
]);
