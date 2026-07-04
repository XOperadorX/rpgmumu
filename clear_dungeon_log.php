<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado.");
}

$playerID = $_SESSION['PlayerID'];
$charID = isset($_POST['CharID']) && $_POST['CharID'] !== '' ? $_POST['CharID'] : null;

// Deleta apenas logs do jogador
$sql = "DELETE dl FROM DungeonLog dl
        JOIN Characters c ON dl.CharID = c.CharID
        WHERE c.PlayerID = ?";

$params = [$playerID];

if($charID){
    $sql .= " AND dl.CharID = ?";
    $params[] = $charID;
}

$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt){
    echo "Logs apagados com sucesso!";
} else {
    echo "Erro ao apagar logs.";
}
?>
