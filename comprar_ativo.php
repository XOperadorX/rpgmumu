<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado.");
}

$playerID = $_SESSION['PlayerID'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $AtivoID = intval($_POST['AtivoID']);
    $Quantidade = max(1,intval($_POST['Quantidade']));

    // Pega dados do ativo
    $stmt = sqlsrv_query($conn, "SELECT * FROM Ativos WHERE AtivoID=?", [$AtivoID]);
    $ativo = ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) ? $row : null;
    if(!$ativo){
        die("Ativo não encontrado.");
    }

    $precoTotal = $ativo['Preco'] * $Quantidade;

    // Pega saldo do jogador
    $stmtPlayer = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
    $player = ($stmtPlayer && $row = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC)) ? $row : null;
    $saldo = $player['MoedaMumu'] ?? 0;

    if($saldo < $precoTotal){
        die("❌ Saldo insuficiente para comprar $Quantidade x {$ativo['Nome']}.");
    }

    // Deduz saldo
    sqlsrv_query($conn, "UPDATE Players SET MoedaMumu=? WHERE PlayerID=?", [$saldo - $precoTotal, $playerID]);

    // Atualiza carteira
    $stmtC = sqlsrv_query($conn, "SELECT * FROM Carteira WHERE PlayerID=? AND AtivoID=?", [$playerID, $AtivoID]);
    if($stmtC && sqlsrv_has_rows($stmtC)){
        sqlsrv_query($conn, "UPDATE Carteira SET Quantidade=Quantidade+? WHERE PlayerID=? AND AtivoID=?", [$Quantidade, $playerID, $AtivoID]);
    } else {
        sqlsrv_query($conn, "INSERT INTO Carteira (PlayerID, AtivoID, Quantidade) VALUES (?,?,?)", [$playerID, $AtivoID, $Quantidade]);
    }

    // Histórico
    sqlsrv_query($conn, "INSERT INTO HistoricoAtivos (PlayerID, AtivoID, Tipo, Quantidade, Preco, DataHora) VALUES (?,?,?,?,?,GETDATE())",
        [$playerID, $AtivoID, "Compra", $Quantidade, $ativo['Preco']]);

    header("Location: bolsa.php");
    exit;
}
