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

// Recebe AccountID via POST
$accountID = isset($_POST['AccountID']) ? intval($_POST['AccountID']) : 0;
if (!$accountID) {
    echo json_encode(['error' => 'ID de conta inválido']);
    exit;
}

// Deleta conta
$sql = "DELETE FROM BankAccounts WHERE AccountID = ?";
$params = [$accountID];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['error' => 'Erro ao excluir conta', 'details' => sqlsrv_errors()]);
    exit;
}

echo json_encode(['message' => 'Conta excluída com sucesso.']);
