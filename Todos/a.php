<?php
session_start();
include "db.php";

// Verifica se o usuário é admin
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    die("Acesso negado.");
}

// Processar alterações
if (isset($_POST['add_value'])) {
    $playerID = (int)$_POST['player_id'];
    $column = $_POST['column'];
    $amount = (int)$_POST['amount'];

    $allowedColumns = ['MoedaMumu', 'Corrente', 'Poupanca', 'Pix', 'Real', 'Level', 'Exp', 'HP'];
    if (in_array($column, $allowedColumns)) {
        $sql = "UPDATE Players SET $column = $column + ? WHERE PlayerID=?";
        sqlsrv_query($conn, $sql, [$amount, $playerID]);
        $msg = "✅ $amount adicionado em $column para o PlayerID $playerID!";
    } else {
        $msg = "❌ Coluna inválida!";
    }
}

// Puxar lista de jogadores
$players = [];
$sqlPlayers = "SELECT PlayerID, Username, MoedaMumu, Corrente, Poupanca, Pix, Real, Level, Exp, HP FROM Players";
$stmt = sqlsrv_query($conn, $sqlPlayers);
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $players[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Admin - Gerenciamento de Jogadores</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f6f7; padding:20px; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ddd; padding:8px; text-align:center; }
th { background:#2c3e50; color:#fff; }
button.add { padding:5px 10px; background:#3498db; color:#fff; border:none; border-radius:4px; cursor:pointer; transition:0.3s; }
button.add:hover { background:#2980b9; }
form.inline { display:inline-block; margin:0; }
.msg { margin-top:15px; font-weight:bold; }
</style>
</head>
<body>

<h1>⚙️ Administração de Jogadores</h1>

<?php if (!empty($msg)) echo "<div class='msg'>$msg</div>"; ?>

<table>
    <tr>
        <th>PlayerID</th>
        <th>Username</th>
        <th>MoedaMumu</th>
        <th>Corrente</th>
        <th>Poupança</th>
        <th>Pix</th>
        <th>Real</th>
        <th>Level</th>
        <th>Exp</th>
        <th>HP</th>
    </tr>
    <?php foreach ($players as $p): ?>
    <tr>
        <td><?= $p['PlayerID'] ?></td>
        <td><?= htmlspecialchars($p['Username']) ?></td>
        <?php foreach (['MoedaMumu','Corrente','Poupanca','Pix','Real','Level','Exp','HP'] as $col): ?>
        <td>
            <?= $p[$col] ?>
            <form class="inline" method="post">
                <input type="hidden" name="player_id" value="<?= $p['PlayerID'] ?>">
                <input type="hidden" name="column" value="<?= $col ?>">
                <input type="number" name="amount" value="0" style="width:50px;">
                <button type="submit" name="add_value" class="add">➕</button>
            </form>
        </td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
