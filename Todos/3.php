<?php
session_start();
include "db.php";

$mensagem = "";

// Redireciona se já estiver logado
if(isset($_SESSION['PlayerID'])){
    header("Location: dashboard.php");
    exit;
}

// LOGIN
if(isset($_POST['username']) && isset($_POST['password'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT PlayerID, PasswordHash FROM Players WHERE Username = ?";
    $stmt = sqlsrv_query($conn, $sql, [$username]);

    if($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        if(password_verify($password, $row['PasswordHash'])){
            $_SESSION['PlayerID'] = $row['PlayerID'];
            header("Location: dashboard.php");
            exit;
        } else {
            $mensagem = "❌ Senha incorreta!";
        }
    } else {
        $mensagem = "❌ Usuário não encontrado!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Entrar - Mumu RPG</title>
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
<h1>🌟 Entrar - Mumu RPG 🌟</h1>
<form method="post">
    <input type="text" name="username" placeholder="Usuário" required><br>
    <input type="password" name="password" placeholder="Senha" required><br>
    <button type="submit">Entrar</button>
</form>
<p><a href="register.php">Criar conta</a></p>
<?php if($mensagem): ?><p class="msg"><?= $mensagem ?></p><?php endif; ?>
</body>
</html>
