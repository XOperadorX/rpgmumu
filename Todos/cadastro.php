<?php
include "db.php";
//include "check_ban.php"; // protege a página


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = $_POST['username'] ?? '';
    $senha = password_hash($_POST['senha'] ?? '', PASSWORD_BCRYPT);
    $codigo = $_POST['codigo'] ?? '';

    // Verifica se o código existe e não foi usado
    $sql = "SELECT * FROM Codigos WHERE Codigo=? AND Usado=0";
    $stmt = sqlsrv_query($conn, $sql, [$codigo]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if(!$row){
        die("❌ Código inválido ou já usado.");
    }

    // Cria player
    $sqlInsert = "INSERT INTO Players (Username, PasswordHash, MoedaMumu, CreatedAt, CodigoUsado)
                  VALUES (?, ?, 0, GETDATE(), ?)";
    $stmtInsert = sqlsrv_query($conn, $sqlInsert, [$username, $senha, $codigo]);

    if($stmtInsert){
        // Marca código como usado
        $sqlUpdate = "UPDATE Codigos SET Usado=1 WHERE Codigo=?";
        sqlsrv_query($conn, $sqlUpdate, [$codigo]);

        echo "✅ Conta criada com sucesso!";
    } else {
        echo "❌ Erro ao criar conta.";
        print_r(sqlsrv_errors());
    }
}
?>
