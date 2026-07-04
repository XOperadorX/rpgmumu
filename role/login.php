<?php
session_start();
include "db.php";

$mensagem = "";

// Redireciona se já estiver logado
if(isset($_SESSION['PlayerID'])){
    header("Location: admin_dashboard.php");
    exit;
}

// LOGIN
if(isset($_POST['username'], $_POST['password'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Consulta o jogador
    $sql = "SELECT PlayerID, PasswordHash, IsBanned, Role FROM Players WHERE Username = ?";
    $stmt = sqlsrv_query($conn, $sql, [$username]);

    if($stmt === false){
        $errors = sqlsrv_errors();
        die("❌ Erro na consulta SQL: " . print_r($errors, true));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if($row){
        // Verifica senha
        if(password_verify($password, $row['PasswordHash'])){
            // Verifica se é admin
            if($row['Role'] !== 'admin'){
                $mensagem = "❌ Acesso negado. Apenas administradores.";
            } elseif($row['IsBanned']){
                $mensagem = "❌ Conta bloqueada!";
            } else {
                // Login válido
                $_SESSION['PlayerID'] = $row['PlayerID'];

                // Atualiza último login
                $loginIP = $_SERVER['REMOTE_ADDR'];
                $sqlUpdate = "UPDATE Players SET LastLoginTime=GETDATE(), LastLoginIP=? WHERE PlayerID=?";
                $paramsUpdate = [$loginIP, $row['PlayerID']];
                $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
                if($stmtUpdate === false){
                    $errors = sqlsrv_errors();
                    die("❌ Erro ao atualizar último login: " . print_r($errors, true));
                }

                // Insere histórico de login
                $sqlInsert = "INSERT INTO LoginHistory (PlayerID, LoginIP) VALUES (?, ?)";
                $stmtInsert = sqlsrv_query($conn, $sqlInsert, [$row['PlayerID'], $loginIP]);
                if($stmtInsert === false){
                    $errors = sqlsrv_errors();
                    die("❌ Erro ao registrar histórico de login: " . print_r($errors, true));
                }

                // Redireciona para dashboard
                header("Location: admin_dashboard.php");
                exit;
            }
        } else $mensagem = "❌ Senha incorreta!";
    } else $mensagem = "❌ Usuário não encontrado!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Login Admin - Mumu RPG</title>
<style>
body{background:#222;color:#fff;font-family:Arial;text-align:center;padding-top:50px;}
form{background:#333;padding:20px;border-radius:10px;display:inline-block;}
input{padding:10px;margin:5px 0;width:200px;border-radius:5px;border:none;}
button{padding:10px 20px;margin-top:10px;border:none;border-radius:5px;background:#ffcc00;color:#000;cursor:pointer;transition:0.3s;}
button:hover{background:#ffd633;}
p.msg{color:orange;margin-top:10px;}
</style>
</head>
<body>
<h1>🌟 Login Admin - Mumu RPG 🌟</h1>
<form method="post">
    <input type="text" name="username" placeholder="Usuário" required><br>
    <input type="password" name="password" placeholder="Senha" required><br>
    <button type="submit">Entrar</button>
</form>
<?php if($mensagem): ?><p class="msg"><?= htmlspecialchars($mensagem) ?></p><?php endif; ?>
</body>
</html>
