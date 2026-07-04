

<nav class="top-bar">
    <a href="admin_dashboard.php">⬅ Voltar</a>
    <a href="new_enemy.php">➕ Novo inimigo</a>
</nav>

<?php
session_start();
include "db.php"; // conexão com o banco


// Atualiza inimigo se formulário enviado
if(isset($_POST['update'])){
    $enemyID = $_POST['EnemyID'];
    $name = $_POST['Name'];
    $hp = intval($_POST['HP']);
    $maxHP = intval($_POST['MaxHP']);
    $mana = intval($_POST['Mana']);
    $maxMana = intval($_POST['MaxMana']);
    $xp = intval($_POST['XP']);
    $level = intval($_POST['Level']);

    $sql = "UPDATE Enemies 
            SET [Name]=?, [HP]=?, [MaxHP]=?, [Mana]=?, [MaxMana]=?, [XP]=?, [Level]=?
            WHERE EnemyID=?";
    $params = [$name, $hp, $maxHP, $mana, $maxMana, $xp, $level, $enemyID];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt){
        $msg = "Inimigo atualizado com sucesso!";
    } else {
        $msg = "Erro ao atualizar inimigo: " . print_r(sqlsrv_errors(), true);
    }
}

// Pega todos os inimigos
$sql = "SELECT * FROM Enemies ORDER BY EnemyID";
$stmt = sqlsrv_query($conn, $sql);
$enemies = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $enemies[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Inimigos</title>
<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    input { width: 60px; }
    .msg { margin: 10px 0; color: green; }

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

</head>
<body>
<h1 style="text-align:center;">⚔️ Editar Inimigos</h1>
<?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

<table>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>HP</th>
        <th>MaxHP</th>
        <th>Mana</th>
        <th>MaxMana</th>
        <th>XP</th>
        <th>Level</th>
        <th>Ação</th>
    </tr>
    <?php foreach($enemies as $e): ?>
    <tr>
        <form method="post">
            <td><?= $e['EnemyID'] ?></td>
            <td><input type="text" name="Name" value="<?= htmlspecialchars($e['Name']) ?>"></td>
            <td><input type="number" name="HP" value="<?= $e['HP'] ?>"></td>
            <td><input type="number" name="MaxHP" value="<?= $e['MaxHP'] ?>"></td>
            <td><input type="number" name="Mana" value="<?= $e['Mana'] ?>"></td>
            <td><input type="number" name="MaxMana" value="<?= $e['MaxMana'] ?>"></td>
            <td><input type="number" name="XP" value="<?= $e['XP'] ?>"></td>
            <td><input type="number" name="Level" value="<?= $e['Level'] ?>"></td>
            <td>
                <input type="hidden" name="EnemyID" value="<?= $e['EnemyID'] ?>">
                <input type="submit" name="update" value="Atualizar">
            </td>
        </form>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
