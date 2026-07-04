<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID']) || $_SESSION['Role'] !== 'admin'){
    die(json_encode(['success'=>false,'msg'=>'Acesso negado']));
}

$data = json_decode(file_get_contents('php://input'), true);

$CharID = $data['CharID'];
$Name = $data['Name'];
$Class = $data['Class'];
$Level = $data['Level'];
$Exp = $data['Exp'];
$HP = $data['HP'];
$MoedaMumu = $data['MoedaMumu'];
$Arma = $data['Arma'];
$Escudo = $data['Escudo'];
$Capacete = $data['Capacete'];
$Armadura = $data['Armadura'];
$Luva = $data['Luva'];
$Calça = $data['Calça'];

// ===== Busca PlayerID do personagem =====
$stmt = sqlsrv_query($conn, "SELECT PlayerID FROM Characters WHERE CharID=?", [$CharID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$PlayerID = $row['PlayerID'] ?? null;

if(!$PlayerID) die(json_encode(['success'=>false]));

try {
    // Atualiza personagem
    $sqlChar = "UPDATE Characters SET Name=?, Class=?, Level=?, Exp=?, HP=?,
                Arma=?, Escudo=?, Capacete=?, Armadura=?, Luva=?, Calça=? WHERE CharID=?";
    $paramsChar = [$Name,$Class,$Level,$Exp,$HP,$Arma,$Escudo,$Capacete,$Armadura,$Luva,$Calça,$CharID];
    $stmtChar = sqlsrv_query($conn, $sqlChar, $paramsChar);

    // Atualiza moedas do jogador
    $sqlPlayer = "UPDATE Players SET MoedaMumu=? WHERE PlayerID=?";
    $stmtPlayer = sqlsrv_query($conn, $sqlPlayer, [$MoedaMumu, $PlayerID]);

    if($stmtChar && $stmtPlayer){
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false]);
    }
} catch(Exception $e){
    echo json_encode(['success'=>false]);
}
