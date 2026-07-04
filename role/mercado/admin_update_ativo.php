<?php
session_start();
include "db.php";

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    die("Acesso negado.");
}

$id = intval($_POST['id'] ?? 0);
$novo = intval($_POST['novo_preco'] ?? 0);

if ($id > 0 && $novo > 0) {
    $sql = "UPDATE MercadoAtivos 
            SET PrecoBase = ?, VariacaoAtual = ((? - PrecoBase) * 100.0 / PrecoBase), UltimaAtualizacao = GETDATE()
            WHERE ID = ?";
    sqlsrv_query($conn, $sql, [$novo, $novo, $id]);
}

header("Location: admin_mercado.php");
exit;
