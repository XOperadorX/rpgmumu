<?php
session_start();
include "../../db.php"; // Ajuste o caminho conforme sua estrutura

header('Content-Type: application/json');

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['error' => 'Acesso negado. Faça login.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

$sql = "SELECT SaldoCorrente, SaldoPoupanca, SaldoPix, SaldoReal FROM BankAccounts WHERE PlayerID = ?";
$params = [$playerID];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['error' => 'Erro na consulta SQL', 'details' => sqlsrv_errors()]);
    exit;
}

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = [
        'SaldoCorrente' => $row['SaldoCorrente'],
        'SaldoPoupanca' => $row['SaldoPoupanca'],
        'SaldoPix'      => $row['SaldoPix'],
        'SaldoReal'     => $row['SaldoReal']
    ];
}

echo json_encode($data);
?>
