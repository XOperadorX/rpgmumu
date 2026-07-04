<?php
session_start();
include "db.php";

$mensagem = "";

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM Players WHERE Username=? AND Password=?";
    $stmt = sqlsrv_query($conn, $sql, [$username, $password]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if($row){
        $_SESSION['PlayerID'] = $row['PlayerID'];
        header("Location: dashboard.php");
    } else {
        $mensagem = "Usuário ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Mumu - Login</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h1>🎮 Bem-vindo ao Mumu</h1>
<form method="post">
<input type="text" name="username" placeholder="Usuário" required><br>
<input type="password" name="password" placeholder="Senha" required><br>
<button type="submit" name="login">Entrar</button>
</form>
<p><?= $mensagem ?></p>
</body>
</html>
