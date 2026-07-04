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


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $AtivoID = intval($_POST['AtivoID']);
    $Quantidade = max(1,intval($_POST['Quantidade']));

    // Pega dados do ativo
    $stmt = sqlsrv_query($conn, "SELECT * FROM Ativos WHERE AtivoID=?", [$AtivoID]);
    $ativo = ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) ? $row : null;
    if(!$ativo){
        die("Ativo não encontrado.");
    }

    // Pega quantidade na carteira
    $stmtC = sqlsrv_query($conn, "SELECT Quantidade FROM Carteira WHERE PlayerID=? AND AtivoID=?", [$playerID, $AtivoID]);
    $carteira = ($stmtC && $row = sqlsrv_fetch_array($stmtC, SQLSRV_FETCH_ASSOC)) ? $row : null;
    if(!$carteira || $carteira['Quantidade'] < $Quantidade){
        die("❌ Você não possui essa quantidade para vender.");
    }

    // Deduz da carteira
    sqlsrv_query($conn, "UPDATE Carteira SET Quantidade=Quantidade-? WHERE PlayerID=? AND AtivoID=?", [$Quantidade, $playerID, $AtivoID]);

    // Remove registro se quantidade ficar zero
    $stmtCheck = sqlsrv_query($conn, "SELECT Quantidade FROM Carteira WHERE PlayerID=? AND AtivoID=?", [$playerID, $AtivoID]);
    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    if($rowCheck['Quantidade'] <= 0){
        sqlsrv_query($conn, "DELETE FROM Carteira WHERE PlayerID=? AND AtivoID=?", [$playerID, $AtivoID]);
    }

    // Credita saldo
    $stmtPlayer = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
    $player = ($stmtPlayer && $row = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC)) ? $row : null;
    $saldo = $player['MoedaMumu'] ?? 0;
    $totalVenda = $ativo['Preco'] * $Quantidade;
    sqlsrv_query($conn, "UPDATE Players SET MoedaMumu=? WHERE PlayerID=?", [$saldo + $totalVenda, $playerID]);

    // Histórico
    sqlsrv_query($conn, "INSERT INTO HistoricoAtivos (PlayerID, AtivoID, Tipo, Quantidade, Preco, DataHora) VALUES (?,?,?,?,?,GETDATE())",
        [$playerID, $AtivoID, "Venda", $Quantidade, $ativo['Preco']]);

    header("Location: bolsa.php");
    exit;
}
