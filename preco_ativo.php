<?php
if(!isset($conn)){ include "db.php"; }
if(!isset($_SESSION)){ session_start(); }

header('Content-Type: application/json');

$ativoNome = $_POST['ativo'] ?? '';
if(!$ativoNome){ echo json_encode(['error'=>"Ativo inválido"]); exit; }

$stmt = sqlsrv_query($conn,"SELECT PrecoAtual FROM Ativos WHERE Nome = ?", [$ativoNome]);
if(!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))){
    echo json_encode(['error'=>"Ativo não encontrado"]); exit;
}

echo json_encode(['preco'=>floatval($row['PrecoAtual'])]);
