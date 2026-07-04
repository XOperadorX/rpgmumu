<?php
// Exemplo simples: compra poção por 10 moedas
$sql = "SELECT MoedaMumu, HP, MaxHP FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, array($playerID));
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if($player['MoedaMumu'] >= 10){
    $newHP = min($player['HP'] + 100, $player['MaxHP']);
    $newMoeda = $player['MoedaMumu'] - 10;
    $update = "UPDATE Players SET HP=?, MoedaMumu=? WHERE PlayerID=?";
    sqlsrv_query($conn, $update, array($newHP, $newMoeda, $playerID));
    echo "Compra realizada!";
}else{
    echo "Moedas insuficientes!";
}
