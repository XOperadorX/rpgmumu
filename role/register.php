<?php
session_start();
include "db.php";

$mensagem = "";

// Redireciona se já estiver logado
if(isset($_SESSION['PlayerID'])){
    header("Location: dashboard.php");
    exit;
}

// CADASTRO
if(isset($_POST['username']) && isset($_POST['password'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Players (Username, PasswordHash) VALUES (?, ?)";
    $stmt = sqlsrv_query($conn, $sql, [$username, $hash]);

    if($stmt){
        $mensagem = "✅ Usuário cadastrado! Agora faça login.";
    } else {
        $mensagem = "❌ Erro no cadastro: " . print_r(sqlsrv_errors(), true);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Cadastro - Mumu RPG</title>
<link rel="stylesheet" href="style.css">
<style>
body{background:#222;color:#fff;font-family:Arial;text-align:center;padding-top:50px;}
form{background:#333;padding:20px;border-radius:10px;display:inline-block;}
input{padding:10px;margin:5px 0;width:200px;border-radius:5px;border:none;}
button{padding:10px 20px;margin-top:10px;border:none;border-radius:5px;background:#ffcc00;color:#000;cursor:pointer;transition:0.3s;}
button:hover{background:#ffd633;}
a{color:#ffcc00;text-decoration:none;}
a:hover{text-decoration:underline;}
p.msg{color:orange;margin-top:10px;}
</style>
</head>
<body>
<h1>🌟 Cadastro - Mumu RPG 🌟</h1>
<form method="post">
    <input type="text" name="username" placeholder="Usuário" required><br>
    <input type="password" name="password" placeholder="Senha" required><br>
    <button type="submit">Cadastrar</button>
</form>
<p><a href="login.php">Já tenho conta</a></p>
<?php if($mensagem): ?><p class="msg"><?= $mensagem ?></p><?php endif; ?>
</body>
</html>
