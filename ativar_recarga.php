<?php
session_start();
include "db.php";
include "check_ban.php";

// Verifica login
if (!isset($_SESSION['PlayerID'])) {
    // Detecta se é uma requisição AJAX (JSON) ou não
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        die(json_encode(['success' => false, 'message' => "⛔ Faça login primeiro."]));
    } else {
        die("⛔ Faça login primeiro.");
    }
}

$playerID = $_SESSION['PlayerID'];

// Define data de expiração: agora + 1 dia
$expiraEm = date('Y-m-d H:i:s', strtotime('+1 days'));

// Atualiza no banco
$sql = "UPDATE Players SET RecargaExpiraEm = ? WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$expiraEm, $playerID]);

if ($stmt) {
    // Retorna JSON se for AJAX, ou mensagem simples se for acesso direto
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode([
            'success' => true,
            'expira' => $expiraEm,
            'timestamp' => strtotime($expiraEm)
        ]);
    } else {
        echo "✅ Recarga total ativada até $expiraEm!";
    }
} else {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => "⚠️ Erro ao ativar recarga"]);
    } else {
        echo "⚠️ Erro ao ativar recarga.";
    }
}
?>
