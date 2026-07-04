<?php
session_start();
include "db.php";
//include "check_ban.php"; // protege a página


$mensagem = "";

// Se já está logado, manda para dashboard
if(isset($_SESSION['PlayerID'])){
    header("Location: dashboard.php");
    exit;
}

// Cadastro com código
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = $_POST['username'] ?? '';
    $senha = $_POST['password'] ?? '';
    $codigo = $_POST['codigo'] ?? '';

    if(!$username || !$senha || !$codigo){
        $mensagem = "❌ Preencha todos os campos.";
    } else {
        // Verifica se o código existe e não foi usado
        $sql = "SELECT * FROM Codigos WHERE Codigo=? AND Usado=0";
        $stmt = sqlsrv_query($conn, $sql, [$codigo]);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if(!$row){
            $mensagem = "❌ Código inválido ou já utilizado.";
        } else {
            // Criptografa a senha
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            // Cria player
            $sqlInsert = "INSERT INTO Players (Username, PasswordHash, MoedaMumu, CreatedAt, CodigoUsado)
                          VALUES (?, ?, 0, GETDATE(), ?)";
            $stmtInsert = sqlsrv_query($conn, $sqlInsert, [$username, $hash, $codigo]);

            if($stmtInsert){
                // Marca código como usado
                $sqlUpdate = "UPDATE Codigos SET Usado=1 WHERE Codigo=?";
                sqlsrv_query($conn, $sqlUpdate, [$codigo]);

                $mensagem = "✅ Conta criada com sucesso! Agora faça login.";
            } else {
                $mensagem = "❌ Erro ao criar conta: " . print_r(sqlsrv_errors(), true);
            }
        }
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
<nav style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <form method="post" style="margin:0; display:flex; gap:10px;">
        <button type="submit" class="btn" name="refresh">🔄 Atualizar</button>
        <a href="dashboard.php" class="btn">⬅️ Voltar</a>
    </form>
</nav>
<h1>🌟 Cadastro - Mumu RPG 🌟</h1>
<form method="post">
    <input type="text" name="username" placeholder="Usuário" required><br>
    <input type="password" name="password" placeholder="Senha" required><br>
    <input type="text" name="codigo" placeholder="Código de convite" required><br>
    <button type="submit">Cadastrar</button>
</form>
<p><a href="login.php">Já tenho conta</a></p>
<?php if($mensagem): ?><p class="msg"><?= $mensagem ?></p><?php endif; ?>
</body>
</html>
