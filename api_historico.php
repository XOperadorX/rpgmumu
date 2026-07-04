<?php
session_start();
include "db.php"; // Conexão PDO

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode([]);
    exit;
}

$playerID = $_SESSION['PlayerID'];

try {
    $stmt = $conn->prepare("SELECT TOP 10 Tipo, Valor, Data FROM BankHistory WHERE PlayerID=? ORDER BY Data DESC");
    $stmt->execute([$playerID]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata datas
    foreach ($history as &$h) {
        if ($h['Data'] instanceof DateTime) {
            $h['Data'] = $h['Data']->format('d/m/Y H:i');
        }
    }

    echo json_encode($history);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>
