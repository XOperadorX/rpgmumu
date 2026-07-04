<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['success' => false, 'message' => "⛔ Faça login primeiro."]);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$slotID = $_POST['slotID'] ?? null;
$frutaID = $_POST['frutaID'] ?? null;

if (!$slotID || !$frutaID) {
    echo json_encode(['success' => false, 'message' => "Escolha slot e semente!"]);
    exit;
}

// Verifica se há sementes
$stmt = sqlsrv_query($conn, "SELECT Quantidade FROM dbo.Sementes WHERE PlayerID = ? AND FrutaID = ?", [$playerID, $frutaID]);
$semente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$semente || $semente['Quantidade'] < 1) {
    echo json_encode(['success' => false, 'message' => "Sem sementes suficientes!"]);
    exit;
}

// Remove semente
sqlsrv_query($conn, "UPDATE dbo.Sementes SET Quantidade = Quantidade - 1 WHERE PlayerID = ? AND FrutaID = ?", [$playerID, $frutaID]);

// Plante
sqlsrv_query($conn, "
    MERGE dbo.PlantacaoFazenda AS target
    USING (SELECT ? AS PlayerID, ? AS SlotID) AS source
    ON target.PlayerID = source.PlayerID AND target.SlotID = source.SlotID
    WHEN MATCHED THEN UPDATE SET FrutaID = ?, DataPlantio = GETDATE()
    WHEN NOT MATCHED THEN INSERT (PlayerID, SlotID, FrutaID, DataPlantio) VALUES (source.PlayerID, source.SlotID, ?, GETDATE());
", [$playerID, $slotID, $frutaID, $frutaID]);

// Registrar histórico
$stmt = sqlsrv_query($conn, "SELECT Nome FROM dbo.Frutas WHERE FrutaID = ?", [$frutaID]);
$nomeFruta = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['Nome'];

sqlsrv_query($conn, "INSERT INTO dbo.HistoricoFazenda (PlayerID, Acao, NomeFruta, Quantidade, DataRegistro)
                     VALUES (?, 'Plantar', ?, 1, GETDATE())", [$playerID, $nomeFruta]);

echo json_encode(['success' => true, 'message' => "✅ Fruta plantada com sucesso!"]);
