<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID']) || $_SESSION['Role'] !== 'admin'){
    die("Acesso negado.");
}

$CharID = $_POST['CharID'];
$PlayerID = $_POST['PlayerID'];

$Name = $_POST['Name'];
$Class = $_POST['Class'];
$Level = $_POST['Level'];
$Exp = $_POST['Exp'];
$HP = $_POST['HP'];
$MoedaMumu = $_POST['MoedaMumu'];
$Arma = $_POST['Arma'];
$Escudo = $_POST['Escudo'];
$Capacete = $_POST['Capacete'];
$Armadura = $_POST['Armadura'];
$Luva = $_POST['Luva'];
$Calça = $_POST['Calça'];

// Atualiza personagem
$sqlChar = "UPDATE Characters SET Name=?, Class=?, Level=?, Exp=?, HP=?,
            Arma=?, Escudo=?, Capacete=?, Armadura=?, Luva=?, Calça=? WHERE CharID=?";
$paramsChar = [$Name,$Class,$Level,$Exp,$HP,$Arma,$Escudo,$Capacete,$Armadura,$Luva,$Calça,$CharID];
$stmtChar = sqlsrv_query($conn, $sqlChar, $paramsChar);

// Atualiza moedas do jogador
$sqlPlayer = "UPDATE Players SET MoedaMumu=? WHERE PlayerID=?";
$paramsPlayer = [$MoedaMumu, $PlayerID];
$stmtPlayer = sqlsrv_query($conn, $sqlPlayer, $paramsPlayer);

if($stmtChar && $stmtPlayer){
    header("Location: admin_personagens.php?success=1");
}else{
    die(print_r(sqlsrv_errors(), true));
}
