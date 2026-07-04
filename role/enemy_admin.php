<?php
session_start();
include "db.php";
/*include "check_ban.php"; */

header('Content-Type: text/html; charset=utf-8');


/*
// Apenas o admin pode acessar (opcional)
if (!isset($_SESSION['PlayerID']) || $_SESSION['PlayerID'] != 1) {
    die("⛔ Acesso negado. Somente admin.");
}
*/


// Ações (add, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $enemyID = intval($_POST['EnemyID'] ?? 0);
    $x = intval($_POST['Xpos'] ?? 0);
    $y = intval($_POST['Ypos'] ?? 0);

    if ($action === 'add') {
        sqlsrv_query($conn, "INSERT INTO dbo.EnemyPositions (EnemyID, Xpos, Ypos) VALUES (?, ?, ?)", [$enemyID, $x, $y]);
    }
    if ($action === 'update') {
        sqlsrv_query($conn, "UPDATE dbo.EnemyPositions SET Xpos=?, Ypos=? WHERE EnemyID=?", [$x, $y, $enemyID]);
    }
    if ($action === 'delete') {
        sqlsrv_query($conn, "DELETE FROM dbo.EnemyPositions WHERE EnemyID=?", [$enemyID]);
    }
    header("Location: enemy_admin.php");
    exit;
}

// Carregar inimigos existentes
$sql = "SELECT e.EnemyID, e.Name, e.Level, e.HP, p.Xpos, p.Ypos
        FROM dbo.Enemies e
        LEFT JOIN dbo.EnemyPositions p ON e.EnemyID = p.EnemyID
        ORDER BY e.EnemyID";
$stmt = sqlsrv_query($conn, $sql);
$enemies = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $enemies[] = $row;
}
?>

<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Admin Inimigos - Mapa</title>
<style>
body{font-family:Arial;background:#111;color:#eee;padding:20px;}
table{border-collapse:collapse;width:100%;max-width:700px;}
th,td{border:1px solid #444;padding:6px;text-align:center;}
th{background:#222;}
input{width:60px;}
button{padding:4px 8px;margin:2px;}
</style>
</head>
<body>
<h2>👹 Editor de Inimigos no Mapa</h2>

<table>
<tr>
<th>ID</th>
<th>Nome</th>
<th>Level</th>
<th>HP</th>
<th>X</th>
<th>Y</th>
<th>Ações</th>
</tr>

<?php foreach($enemies as $e): ?>
<tr>
<form method="post">
    <td><?php echo $e['EnemyID']; ?><input type="hidden" name="EnemyID" value="<?php echo $e['EnemyID']; ?>"></td>
    <td><?php echo htmlspecialchars($e['Name']); ?></td>
    <td><?php echo $e['Level']; ?></td>
    <td><?php echo $e['HP']; ?></td>
    <td><input type="number" name="Xpos" value="<?php echo $e['Xpos']; ?>"></td>
    <td><input type="number" name="Ypos" value="<?php echo $e['Ypos']; ?>"></td>
    <td>
        <button name="action" value="update">💾 Salvar</button>
        <button name="action" value="delete" onclick="return confirm('Remover inimigo?')">❌ Remover</button>
    </td>
</form>
</tr>
<?php endforeach; ?>

<tr>
<form method="post">
    <td><input type="number" name="EnemyID" placeholder="ID"></td>
    <td colspan="2" style="text-align:left;">Novo inimigo existente</td>
    <td></td>
    <td><input type="number" name="Xpos" placeholder="X"></td>
    <td><input type="number" name="Ypos" placeholder="Y"></td>
    <td><button name="action" value="add">➕ Adicionar</button></td>
</form>
</tr>
</table>

</body>
</html>
