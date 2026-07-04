<br>
<nav class="top-bar">
    <a href="enemies.php">⬅ Voltar</a>
</nav>

<style>
.top-bar {
    display: flex;
    justify-content: center;      /* centraliza horizontalmente */
    gap: 30px;                    /* espaço entre os links */
    padding: 12px 0;              /* padding em cima e embaixo */
    background-color: #2c3e50;    /* cor de fundo da barra */
    border-radius: 8px;           /* cantos arredondados */
    box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* sombra suave */
}

.top-bar a {
    text-decoration: none;        /* remove sublinhado */
    color: #ecf0f1;               /* cor do texto */
    font-weight: 600;
    font-size: 16px;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;    /* animação suave */
}

.top-bar a:hover {
    background-color: #3498db;    /* muda fundo ao passar o mouse */
    color: #fff;                  /* muda a cor do texto */
    transform: translateY(-2px);  /* leve elevação ao hover */
    box-shadow: 0 4px 6px rgba(0,0,0,0.2); /* sombra mais forte */
}
</style>
<br>

<?php
session_start();
include "db.php"; // Conexão com o banco


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $level = intval($_POST['level'] ?? 1);
    $hp = intval($_POST['hp'] ?? 100);
    $maxHP = intval($_POST['maxhp'] ?? $hp);
    $mana = intval($_POST['mana'] ?? 50);
    $maxMana = intval($_POST['maxmana'] ?? $mana);
    $xp = intval($_POST['xp'] ?? 10);
    $loot = trim($_POST['loot'] ?? ''); // Pode ser string ou lista separada por vírgula

    if ($name === '') {
        die("Nome do inimigo é obrigatório.");
    }

    // Insere o inimigo
    $sql = "INSERT INTO Enemies (Name, HP, MaxHP, XP, Loot, Mana, MaxMana, Level) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $params = [$name, $hp, $maxHP, $xp, $loot, $mana, $maxMana, $level];
    $stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    // Mensagem de sucesso
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); // evita problemas com nomes
    echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Inimigo criado</title>
    <script>
        // Redireciona após 2 segundos
        setTimeout(function() {
            window.location.href = 'new_enemy.php';
        }, 2000);
    </script>
</head>
<body>
    <p>Inimigo '$name' criado com sucesso! Redirecionando...</p>
</body>
</html>";
    exit();
} else {
    echo "Erro ao criar inimigo: ";
    print_r(sqlsrv_errors());
}

} else {
    // Formulário HTML
    echo '<form method="POST">
        Nome: <input type="text" name="name" required><br>
        Level: <input type="number" name="level" value="1" required><br>
        HP: <input type="number" name="hp" value="100" required><br>
        Max HP: <input type="number" name="maxhp" value="100" required><br>
        Mana: <input type="number" name="mana" value="50"><br>
        Max Mana: <input type="number" name="maxmana" value="50"><br>
        XP: <input type="number" name="xp" value="10"><br>
        Loot (string ou itens separados por vírgula): <input type="text" name="loot"><br>
        <button type="submit">Criar Inimigo</button>
    </form>';
}
?>


