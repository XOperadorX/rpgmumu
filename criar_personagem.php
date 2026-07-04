<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

if($_POST && isset($_POST['action']) && $_POST['action'] === "createChar"){
    $name = $_POST['name'];
    $class = $_POST['class'];

    $sql = "INSERT INTO Characters (PlayerID, Name, Class, Level, Exp, HP, MaxHP, Mana, MaxMana) 
            VALUES (?, ?, ?, 1, 0, 100, 100, 50, 50)";
    $stmt = sqlsrv_query($conn, $sql, [$playerID, $name, $class]);

    if($stmt){
        // Redireciona para o dashboard após criar personagem
        header("Location: dashboard.php");
        exit;
    } else {
        $mensagem = "❌ Erro ao criar personagem: " . print_r(sqlsrv_errors(), true);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Criar Personagem</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <form method="post" style="margin:0; display:flex; gap:10px;">
        <button type="submit" class="btn" name="refresh">🔄 Atualizar</button>
        <a href="dashboard.php" class="btn">⬅️ Voltar</a>
    </form>
</nav>

<h2>✨ Criar Personagem</h2>
<form method="post">
    <input type="hidden" name="action" value="createChar">
    <input type="text" name="name" placeholder="Nome do Personagem" required><br>
    <select name="class" required>
        <option value="">Selecione a Classe</option>
        <option value="Knight">⚔️ Knight</option>
        <option value="Wizard">🪄 Wizard</option>
        <option value="Elf">🏹 Elf</option>
    </select><br>
    <button type="submit">Criar</button>
</form>

<?php if(!empty($mensagem)) echo "<p>$mensagem</p>"; ?>
</body>
</html>
