<?php
session_start();
include "db.php";
header('Content-Type: application/json');

$playerID = $_SESSION['PlayerID'] ?? null;
$itemID = $_POST['ItemID'] ?? null;

if(!$playerID || $itemID===null){
    echo json_encode(['success'=>false,'error'=>'Dados inválidos']);
    exit;
}

$itemID = $itemID;
$itemNome = '';
$itemDados = [];

// Item do banco
if($itemID>0){
    $stmt = sqlsrv_query($conn,"SELECT * FROM dbo.Items WHERE ItemID=? AND PlayerID=?", [$itemID,$playerID]);
    $itemDados = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
    if(!$itemDados){
        echo json_encode(['success'=>false,'error'=>'Item não encontrado no banco']);
        exit;
    }
    $itemNome = $itemDados['Nome'];
} else {
    if(!isset($_SESSION['inventario'][$itemID])){
        echo json_encode(['success'=>false,'error'=>'Item não encontrado na sessão']);
        exit;
    }
    $itemDados = $_SESSION['inventario'][$itemID];
    $itemNome = $itemDados['nome'] ?? 'Desconhecido';
}

// Armazena equipamento na sessão
if(!isset($_SESSION['equipamentos'])) $_SESSION['equipamentos']=[];
$slot = $itemDados['categoria'] ?? 'geral';
$_SESSION['equipamentos'][$slot] = [
    'itemID'=>$itemID,
    'nome'=>$itemNome,
    'dados'=>$itemDados
];

echo json_encode(['success'=>true,'slot'=>$slot,'nome'=>$itemNome]);
