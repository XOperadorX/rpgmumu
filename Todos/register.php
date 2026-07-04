<?php
include "db.php";

if($_POST){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Players (Username, PasswordHash) VALUES (?, ?)";
    $params = [$username, $hash];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if($stmt){
        echo "Usuário do Mumu cadastrado com sucesso!";
    } else {
        echo "Erro: " . print_r(sqlsrv_errors(), true);
    }
}
?>

<form method="post">
    Usuário: <input type="text" name="username"><br>
    Senha: <input type="password" name="password"><br>
    <button type="submit">Cadastrar</button>
</form>
