<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página

if (!isset($_SESSION['PlayerID'])) {
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

try {
    // Atualiza o status do jogador para bloqueado
    $stmt = sqlsrv_query($conn, "UPDATE Players SET IsBanned = 1 WHERE PlayerID = ?", [$playerID]);
    if ($stmt === false) {
        throw new Exception(print_r(sqlsrv_errors(), true));
    }

    // Destroi sessão (desloga imediatamente)
    session_destroy();

    echo "<h2>🚫 Sua conta foi bloqueada com sucesso!</h2>";
    echo "<p>Você não poderá mais acessar até que seja reativada por um administrador.</p>";
    echo "<a href='index.php'>Voltar à página inicial</a>";

} catch (Exception $e) {
    die("❌ Erro ao bloquear a conta: " . $e->getMessage());
}
?>
