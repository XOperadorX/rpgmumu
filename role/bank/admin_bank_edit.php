<?php
session_start();
include "../../db.php"; // Ajuste caminho

header('Content-Type: application/json');

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Verifica se é admin
$playerID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID=?", [$playerID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row || $row['Role'] !== 'admin') {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Recebe dados via POST
$accountID = isset($_POST['AccountID']) ? intval($_POST['AccountID']) : 0;
$field = isset($_POST['Field']) ? $_POST['Field'] : '';
$balance = isset($_POST['Balance']) ? floatval($_POST['Balance']) : 0;

// Valida campos
$validFields = ['Corrente','Poupanca','Pix','Real'];
if (!$accountID || !in_array($field, $validFields)) {
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Atualiza saldo
$sql = "UPDATE BankAccounts SET [$field] = ?, LastUpdate = GETDATE() WHERE AccountID = ?";
$params = [$balance, $accountID];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['error' => 'Erro ao atualizar saldo', 'details' => sqlsrv_errors()]);
    exit;
}

echo json_encode(['message' => "Saldo atualizado com sucesso ($fiel]()_
