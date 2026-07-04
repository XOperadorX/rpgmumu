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

$moedaMumu = floatval($_POST['corrente'] ?? 0); // saldo em MoedaMumu

// Atualiza apenas o saldo corrente (MoedaMumu) no banco
$sql = "UPDATE [MumuDB].[dbo].[BankAccounts] SET Corrente = ? WHERE PlayerID = ?";
$params = [$moedaMumu, $playerID];
$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt === false){
    die(print_r(sqlsrv_errors(), true));
}

echo "ok";
