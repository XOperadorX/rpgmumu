<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) exit;

$playerID = $_SESSION['PlayerID'];
$valor = intval($_POST['valor']);

// Puxar saldo
$sql = "SELECT MoedaMumu, Poupanca FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, array($playerID));
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if($valor <= $player['MoedaMumu']){
    $newCorrente = $player['MoedaMumu'] - $valor;
    $newPoupanca = $player['Poupanca'] + $valor;
    $update = "UPDATE Players SET MoedaMumu=?, Poupanca=? WHERE PlayerID=?";
    sqlsrv_query($conn, $update, array($newCorrente, $newPoupanca, $playerID));
    echo "ok";
} else {
    echo "erro";
}
