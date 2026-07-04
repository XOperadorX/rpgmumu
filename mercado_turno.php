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

$charID = intval($_POST['charID'] ?? 0);
$tipo = $_POST['tipo'] ?? '';

if(!$charID || !in_array($tipo, ['hp','mana'])){
    echo json_encode(['erro'=>'Dados inválidos']);
    exit;
}

// Pega saldo de moedas
$stmtPlayer = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
$player = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC);
$moedas = $player['MoedaMumu'] ?? 0;

$preco = 10;
if($moedas < $preco){
    echo json_encode(['erro'=>'💸 Moedas insuficientes']);
    exit;
}

// Atualiza personagem
if($tipo === 'hp'){
    sqlsrv_query($conn, "UPDATE Characters SET HP = MaxHP WHERE CharID=? AND PlayerID=?", [$charID,$playerID]);
} else {
    sqlsrv_query($conn, "UPDATE Characters SET Mana = MaxMana WHERE CharID=? AND PlayerID=?", [$charID,$playerID]);
}

// Deduz moedas
sqlsrv_query($conn, "UPDATE Players SET MoedaMumu = MoedaMumu - ? WHERE PlayerID=?", [$preco,$playerID]);

// Retorna dados atualizados
$stmtChar = sqlsrv_query($conn, "SELECT HP, MaxHP, Mana, MaxMana FROM Characters WHERE CharID=? AND PlayerID=?", [$charID,$playerID]);
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
$stmtPlayer = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
$player = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC);

echo json_encode([
    'HP'=>$char['HP'],
    'MaxHP'=>$char['MaxHP'],
    'Mana'=>$char['Mana'],
    'MaxMana'=>$char['MaxMana'],
    'moedas'=>$player['MoedaMumu']
]);
