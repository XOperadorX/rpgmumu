<?php
session_start();
if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Mumu</title>
    <style>
        body { font-family: Arial; background:#222; color:#f1f1f1; text-align:center; padding:30px; }
        h1 { color:#ffcc00; }
        a { display:inline-block; margin:10px; padding:10px 20px; border:none; background:#444; color:#fff; border-radius:5px; text-decoration:none; transition:0.3s; }
        a:hover { background:#ffcc00; color:#000; }
    </style>
</head>
<body>
    <h1>🏰 Painel do Mumu</h1>
    <p>Bem-vindo, aventureiro!</p>

    <!-- Links para páginas separadas -->
    <a href="personagens.php">👥 Ver Personagens</a>
    <a href="criar_personagem.php">✨ Criar Novo Personagem</a>
    <a href="dung.php">🗡️ Dungeon</a>
    <a href="logout.php">🚪 Sair</a>
</body>
</html>
