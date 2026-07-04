<?php
session_start();
include "db.php";

$msg = "";

// ======================
// Adicionar loot
// ======================
if(isset($_POST['add_loot'])){
    $enemyID = intval($_POST['EnemyID']);
    $itemName = trim($_POST['ItemName']);
    $dropChance = intval($_POST['DropChance']);

    if(!$enemyID){
        $msg = "Selecione um inimigo válido.";
    } elseif(empty($itemName)){
        $msg = "Informe o nome do item.";
    } elseif($dropChance < 1 || $dropChance > 100){
        $msg = "A chance deve estar entre 1 e 100.";
    } else {
        $stmt = sqlsrv_query($conn,
            "INSERT INTO EnemyLoot (EnemyID, ItemName, DropChance) VALUES (?, ?, ?)",
            [$enemyID, $itemName, $dropChance]
        );

        if($stmt){
            $msg = "Loot adicionado com sucesso!";
        } else {
            $errors = sqlsrv_errors();
            $msg = "Erro ao adicionar loot: " . print_r($errors, true);
        }
    }
}

// ======================
// Editar loot
// ======================
if(isset($_POST['edit_loot'])){
    $lootID = intval($_POST['LootID']);
    $itemName = trim($_POST['ItemName']);
    $dropChance = intval($_POST['DropChance']);

    if(!$lootID){
        $msg = "Loot inválido.";
    } elseif(empty($itemName)){
        $msg = "Informe o nome do item.";
    } elseif($dropChance < 1 || $dropChance > 100){
        $msg = "A chance deve estar entre 1 e 100.";
    } else {
        $stmt = sqlsrv_query($conn,
            "UPDATE EnemyLoot SET ItemName=?, DropChance=? WHERE LootID=?",
            [$itemName, $dropChance, $lootID]
        );

        if($stmt){
            $msg = "Loot atualizado com sucesso!";
        } else {
            $errors = sqlsrv_errors();
            $msg = "Erro ao atualizar loot: " . print_r($errors, true);
        }
    }
}

// ======================
// Remover loot
// ======================
if(isset($_POST['delete_loot'])){
    $lootID = intval($_POST['LootID']);
    if(!$lootID){
        $msg = "Loot inválido.";
    } else {
        $stmt = sqlsrv_query($conn, "DELETE FROM EnemyLoot WHERE LootID=?", [$lootID]);

        if($stmt){
            $msg = "Loot removido com sucesso!";
        } else {
            $errors = sqlsrv_errors();
            $msg = "Erro ao remover loot: " . print_r($errors, true);
        }
    }
}

// ======================
// Lista de inimigos
// ======================
$enemies = [];
$stmtEnemies = sqlsrv_query($conn, "SELECT EnemyID, Name FROM Enemies ORDER BY Name");
if($stmtEnemies){
    while($row = sqlsrv_fetch_array($stmtEnemies, SQLSRV_FETCH_ASSOC)){
        $enemies[$row['EnemyID']] = $row['Name'];
    }
}

// ======================
// Loot existente
// ======================
$allLoot = [];
$stmtLoot = sqlsrv_query($conn, "SELECT l.LootID, l.EnemyID, l.ItemName, l.DropChance, e.Name AS EnemyName
                                FROM EnemyLoot l
                                JOIN Enemies e ON l.EnemyID = e.EnemyID
                                ORDER BY e.Name, l.ItemName");
if($stmtLoot){
    while($row = sqlsrv_fetch_array($stmtLoot, SQLSRV_FETCH_ASSOC)){
        $allLoot[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Gerenciar Loot dos Inimigos</title>
<style>
body { font-family: Arial; background:#1c1c1c; color:#fff; padding:20px; }
table { border-collapse: collapse; margin-top:20px; width:90%; }
table, th, td { border:1px solid #fff; padding:8px; }
input, select { padding:5px; margin-right:10px; }
button { padding:5px 10px; cursor:pointer; margin-right:5px; }
.msg { margin-top:10px; color:#2ecc71; }
form.inline { display:inline; }
nav.top-bar a { color:#fff; margin-right:10px; text-decoration:none; }
</style>
</head>
<body>

<nav class="top-bar">
    <a href="admin_dashboard.php">⬅ Voltar</a>
    <a href="new_enemy.php">➕ Novo inimigo</a>
</nav>

<h1>Gerenciar Loot dos Inimigos</h1>

<?php if($msg) echo "<div class='msg'>$msg</div>"; ?>

<h2>Adicionar Loot</h2>
<form method="post">
    <select name="EnemyID" required>
        <option value="">Selecione o inimigo</option>
        <?php foreach($enemies as $id=>$name): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="ItemName" placeholder="Nome do Item" required>
    <input type="number" name="DropChance" placeholder="% de chance" min="1" max="100" value="100" required>
    <button type="submit" name="add_loot">Adicionar</button>
</form>

<h2>Loot Existente</h2>
<table>
<tr>
    <th>Inimigo</th>
    <th>Item</th>
    <th>Chance (%)</th>
    <th>Ações</th>
</tr>

<?php foreach($allLoot as $l): ?>
<tr>
    <td><?= htmlspecialchars($l['EnemyName']) ?></td>
    <td>
        <form method="post" class="inline">
            <input type="hidden" name="LootID" value="<?= $l['LootID'] ?>">
            <input type="text" name="ItemName" value="<?= htmlspecialchars($l['ItemName']) ?>" required>
    </td>
    <td>
            <input type="number" name="DropChance" value="<?= $l['DropChance'] ?>" min="1" max="100" required>
    </td>
    <td>
            <button type="submit" name="edit_loot">Editar</button>
        </form>

        <form method="post" class="inline">
            <input type="hidden" name="LootID" value="<?= $l['LootID'] ?>">
            <button type="submit" name="delete_loot" onclick="return confirm('Deseja realmente remover este loot?')">Remover</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
