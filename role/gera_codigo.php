<?php
include "db.php";

// === Gerar código único (8 caracteres, letras + números, maiúsculo) ===
$codigo = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

// === Inserir no banco ===
$sql = "INSERT INTO Codigos (Codigo, Usado) VALUES (?, 0)";
$stmt = sqlsrv_query($conn, $sql, [$codigo]);

if($stmt){
    echo "✅ Código gerado: <strong>$codigo</strong>";
} else {
    echo "❌ Erro ao gerar código.<br>";
    print_r(sqlsrv_errors(), true);
}
?>
