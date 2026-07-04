<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['success' => false, 'message' => "⛔ Faça login primeiro."]);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// Pega os slots enviados via POST
$slotsPost = isset($_POST['slots']) ? explode(',', $_POST['slots']) : [];
$colhidas = 0;

foreach($slotsPost as $slotID) {
    $slotID = intval($slotID);

    // Busca informações do slot específico
    $slotData = safeQuery($conn, "
        SELECT pf.SlotID, pf.FrutaID, f.Nome, f.TempoCrescimento, pf.DataPlantio
        FROM dbo.PlantacaoFazenda pf
        JOIN dbo.Frutas f ON pf.FrutaID = f.FrutaID
        WHERE pf.PlayerID = ? AND pf.SlotID = ?
    ", [$playerID, $slotID]);

    if (!$slotData) continue; // Slot vazio ou inválido
    $slot = $slotData[0];

    $plantio = $slot['DataPlantio'];
    $tempoSegundos = $slot['TempoCrescimento'] * 60;

    if ((strtotime($plantio->format('Y-m-d H:i:s')) + $tempoSegundos) <= time()) {
        // Adiciona ao inventário
        sqlsrv_query($conn, "
            MERGE dbo.InventarioFrutas AS target
            USING (SELECT ? AS PlayerID, ? AS FrutaID) AS source
            ON target.PlayerID = source.PlayerID AND target.FrutaID = source.FrutaID
            WHEN MATCHED THEN UPDATE SET Quantidade = Quantidade + 1
            WHEN NOT MATCHED THEN INSERT (PlayerID, FrutaID, Quantidade) VALUES (source.PlayerID, ?, 1);
        ", [$playerID, $slot['FrutaID'], $slot['FrutaID']]);

        // Limpa slot
        sqlsrv_query($conn, "UPDATE dbo.PlantacaoFazenda SET FrutaID = NULL, DataPlantio = NULL WHERE PlayerID = ? AND SlotID = ?", [$playerID, $slotID]);

        // Registrar histórico
        sqlsrv_query($conn, "INSERT INTO dbo.HistoricoFazenda (PlayerID, Acao, NomeFruta, Quantidade, DataRegistro)
                             VALUES (?, 'Colher', ?, 1, GETDATE())", [$playerID, $slot['Nome']]);

        $colhidas++;
    }
}

echo json_encode(['success' => true, 'message' => "✅ {$colhidas} frutas colhidas!"]);
