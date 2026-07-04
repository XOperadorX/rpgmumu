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


// Pega dados do jogador
$sql = "SELECT Username, MoedaMumu, CreatedAt, LastLoginTime, LastLoginIP, IsBanned FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$player){
    echo json_encode(['error'=>'Jogador não encontrado']);
    exit;
}

// Calcula dias restantes antes do bloqueio (3 dias sem logar)
$lastLogin = $player['LastLoginTime'] instanceof DateTime ? $player['LastLoginTime'] : new DateTime($player['LastLoginTime']);
$now = new DateTime();
$diff = $now->diff($lastLogin);
$daysWithoutLogin = (int)$diff->format('%a');
$maxDays = 3;
$remainingDays = max($maxDays - $daysWithoutLogin, 0);

// Bloqueia automaticamente se passou dos 3 dias
$isBanned = $player['IsBanned'];
if($remainingDays <= 0 && !$isBanned){
    $sqlBan = "UPDATE Players SET IsBanned=1 WHERE PlayerID=?";
    sqlsrv_query($conn, $sqlBan, [$playerID]);
    $isBanned = 1;
}

// Percentual da barra
$healthPercent = ($remainingDays / $maxDays) * 100;

echo json_encode([
    'username' => $player['Username'],
    'moeda' => (int)$player['MoedaMumu'],
    'createdAt' => $player['CreatedAt'] instanceof DateTime ? $player['CreatedAt']->format('d/m/Y H:i') : $player['CreatedAt'],
    'lastLogin' => $lastLogin->format('d/m/Y H:i'),
    'lastLoginIP' => $player['LastLoginIP'] ?? '-',
    'isBanned' => (bool)$isBanned,
    'remainingDays' => $remainingDays,
    'healthPercent' => $healthPercent
]);
