<?php
include "db.php"; // sua conexão com SQL Server
include "check_ban.php"; // protege a página


// Gera um código único de 8 caracteres
$codigo = strtoupper(substr(md5(uniqid()), 0, 8));

// Insere no banco
$sql = "INSERT INTO Codigos (Codigo) VALUES (?)";
$stmt = sqlsrv_query($conn, $sql, [$codigo]);

if($stmt){
    echo "✅ Código gerado: <strong>$codigo</strong>";
} else {
    echo "❌ Erro ao gerar código.";
    print_r(sqlsrv_errors());
}
?>
