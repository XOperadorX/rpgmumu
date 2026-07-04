<?php
if (!isset($conn)) {
    include "db.php"; // Garante que a conexão está disponível
}

if (!isset($_SESSION)) {
    session_start();
}

$playerID = $_SESSION['PlayerID'] ?? null;

if (!$playerID) {
    die("⛔ Acesso negado. Faça login.");
}

// Verifica se o jogador está banido
$stmt = sqlsrv_query($conn, "SELECT Banido FROM Players WHERE PlayerID = ?", [$playerID]);
if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if (!empty($row['Banido']) && $row['Banido'] == 1) {
        die("⛔ Você está banido e não pode acessar o jogo.");
    }
}

$valorRestaurar = 500; // custo para restaurar

// Pega o saldo Pix do jogador
$sql = "SELECT Pix FROM BankAccounts WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
if($stmt === false){
    die("Erro ao consultar saldo Pix.");
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$saldo = $row['Pix'] ?? 0;

// Verifica se o jogador tem saldo suficiente
if($saldo < $valorRestaurar){
    // Redireciona para o dashboard com mensagem de erro via GET
    header("Location: dashboard.php?erro=" . urlencode("⛔ Saldo insuficiente. Você precisa de $valorRestaurar Pix para restaurar."));
    exit;
}

// Deduz o valor
$sql = "UPDATE BankAccounts SET Pix = Pix - ? WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$valorRestaurar, $playerID]);
if($stmt === false){
    die("Erro ao deduzir Pix.");
}

// Atualiza HP, Mana e Power do personagem
$sql = "UPDATE Characters 
        SET HP = MaxHP, 
            Mana = MaxMana,
            Power = MaxPower
        WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
if($stmt === false){
    die("Erro ao restaurar atributos.");
}

// Atualiza também na sessão
if(isset($_SESSION['char'])){
    $_SESSION['char']['HP'] = $_SESSION['char']['MaxHP'];
    $_SESSION['char']['Mana'] = $_SESSION['char']['MaxMana'];
    $_SESSION['char']['Power'] = $_SESSION['char']['MaxPower'];
}

// Redireciona de volta para o dashboard com mensagem de sucesso
header("Location: dashboard.php?sucesso=" . urlencode("✨ Atributos restaurados com sucesso!"));
exit;
?>
