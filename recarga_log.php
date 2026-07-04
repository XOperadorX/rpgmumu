<?php
if (!isset($conn)) include "db.php";
if (!isset($_SESSION)) session_start();

// ====================================
// 🔐 Verifica autenticação
// ====================================
$playerID = $_SESSION['PlayerID'] ?? null;
if (!$playerID) {
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// ====================================
// ⚙️ Opção: se o admin quiser ver outro jogador
// ====================================
$adminMode = false;
if (isset($_GET['player']) && is_numeric($_GET['player'])) {
    $reqPlayerID = (int) $_GET['player'];

    // Somente admin pode consultar outro player
    if (!empty($_SESSION['IsAdmin']) && $_SESSION['IsAdmin'] == 1) {
        $playerID = $reqPlayerID;
        $adminMode = true;
    }
}

// ====================================
// 📜 Busca logs de recarga do jogador
// ====================================
$sql = "SELECT TOP 50 LogID, PlayerID, DataHora, DuracaoRestante, Status
        FROM RecargaLog
        WHERE PlayerID = ?
        ORDER BY LogID DESC";

$stmt = sqlsrv_query($conn, $sql, [$playerID]);
if ($stmt === false) {
    echo json_encode(['error' => 'Erro ao consultar logs', 'detalhes' => sqlsrv_errors()]);
    exit;
}

$logs = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $dataFormatada = ($row['DataHora'] instanceof DateTime)
        ? $row['DataHora']->format('d/m/Y H:i:s')
        : date('d/m/Y H:i:s', strtotime($row['DataHora']));

    $logs[] = [
        'LogID' => $row['LogID'],
        'PlayerID' => $row['PlayerID'],
        'DataHora' => $dataFormatada,
        'DuracaoRestante' => $row['DuracaoRestante'],
        'Status' => $row['Status']
    ];
}

// ====================================
// 📦 Retorno final em JSON
// ====================================
echo json_encode([
    'AdminMode' => $adminMode,
    'PlayerID' => $playerID,
    'Logs' => $logs
], JSON_UNESCAPED_UNICODE);
