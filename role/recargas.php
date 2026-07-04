<?php
session_start();
include "db.php"; // Conexão com o banco
include "check_ban.php"; // Verifica se o usuário está banido

// ⚠️ Apenas admins podem zerar recargas
if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'admin') {
    die("⛔ Acesso negado. Apenas admins podem realizar essa ação.");
}

// Inicializa log
$log = [];

// Executa update para zerar todas as recargas ativas
$sql = "UPDATE BankAccounts SET RecargaAtiva = 0 WHERE RecargaAtiva = 1";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    $errors = sqlsrv_errors();
    foreach ($errors as $error) {
        $log[] = "Erro SQL: ".$error['message'];
    }
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível zerar as recargas.",
        "detalhes" => $log
    ]);
    exit;
}

// Confirmação
$log[] = "✅ Todas as recargas ativas foram zeradas com sucesso.";
echo json_encode([
    "status" => "sucesso",
    "mensagem" => "Recargas zeradas.",
    "detalhes" => $log
]);
?>
