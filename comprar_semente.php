<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['success' => false, 'message' => "⛔ Faça login primeiro."]);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$frutaID = $_POST['frutaID'] ?? null;

if (!$frutaID) {
    echo json_encode(['success' => false, 'message' => "Escolha uma semente!"]);
    exit;
}

// Pega o preço da semente
$stmt = sqlsrv_query($conn, "SELECT Nome, PrecoSemente FROM dbo.Frutas WHERE FrutaID = ?", [$frutaID]);
$fruta = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$fruta) {
    echo json_encode(['success' => false, 'message' => "Semente inválida!"]);
    exit;
}

// Pega saldo
$stmt = sqlsrv_query($conn, "SELECT Poupanca FROM dbo.BankAccounts WHERE PlayerID = ?", [$playerID]);
$conta = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($conta['Poupanca'] < $fruta['PrecoSemente']) {
    echo json_encode(['success' => false, 'message' => "💎 Saldo insuficiente!"]);
    exit;
}

// Deduz saldo
sqlsrv_query($conn, "UPDATE dbo.BankAccounts SET Poupanca = Poupanca - ? WHERE PlayerID = ?", [$fruta['PrecoSemente'], $playerID]);

// Adiciona semente ao inventário
sqlsrv_query($conn, "
    MERGE dbo.Sementes AS target
    USING (SELECT ? AS PlayerID, ? AS FrutaID) AS source
    ON target.PlayerID = source.PlayerID AND target.FrutaID = source.FrutaID
    WHEN MATCHED THEN UPDATE SET Quantidade = Quantidade + 1
    WHEN NOT MATCHED THEN INSERT (PlayerID, FrutaID, Quantidade) VALUES (source.PlayerID, source.FrutaID, 1);
", [$playerID, $frutaID]);

// Registrar no histórico
sqlsrv_query($conn, "INSERT INTO dbo.HistoricoFazenda (PlayerID, Acao, NomeFruta, Quantidade, DataRegistro)
                     VALUES (?, 'Comprar Semente', ?, 1, GETDATE())", [$playerID, $fruta['Nome']]);

echo json_encode(['success' => true, 'message' => "✅ Semente comprada com sucesso!"]);
