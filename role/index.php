<?php
session_start();
$mensagem = ""; // Caso queira mostrar mensagens, como "Conta criada com sucesso"
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Mumu RPG</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background:#222; color:#fff; font-family: Arial, sans-serif; text-align: center; padding-top:50px; }
        a { display:inline-block; margin:10px; padding:10px 20px; background:#ffcc00; color:#000; text-decoration:none; border-radius:5px; transition:0.3s; }
        a:hover { background:#ffd633; }
        p.msg { color: orange; }
    </style>
</head>
<body>
    <h1>🌟 Bem-vindo ao Mumu RPG ADM🌟</h1>

    <?php if(isset($_SESSION['PlayerID'])): ?>
        <p>Você já está logado!</p>
        <a href="admin_dashboard.php">Ir para o Painel ADM</a>
        <a href="logout.php">Sair</a>
    <?php else: ?>
        <p>Faça login ou crie sua conta para começar a aventura.</p>
        <p><a href="login.php">🚪 Entrar na Conta</a>
        <a href="register.php">📝 Criar Conta</a></p>
        <?php if($mensagem): ?><p class="msg"><?= $mensagem ?></p><?php endif; ?>
    <?php endif; ?>
</body>
</html>
