<?php
session_start();
include "db.php";

// ===== Verifica se é admin =====
if(!isset($_SESSION['PlayerID']) || $_SESSION['Role'] !== 'admin'){
    die("Acesso negado. Apenas administradores podem acessar.");
}

// ===== Busca todos os personagens com seus jogadores =====
$sql = "SELECT c.CharID, c.Name, c.Class, c.Level, c.Exp, c.HP,
               c.Arma, c.Escudo, c.Capacete, c.Armadura, c.Luva, c.Calça,
               p.PlayerID, p.NomeJogador, p.MoedaMumu
        FROM Characters c
        JOIN Players p ON c.PlayerID = p.PlayerID
        ORDER BY p.PlayerID, c.CharID";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Administração de Personagens</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
table { border-collapse: collapse; width: 90%; margin: 20px auto; text-align: center; }
th, td { padding: 8px; border: 1px solid #333; }
th { background: #333; color: #fff; }
input[type="text"], input[type="number"] { width: 100px; }
.btn { padding: 6px 12px; text-decoration: none; border-radius: 5px; margin: 2px; display:inline-block; }
.btn-save { background: #4CAF50; color: #fff; }
.btn-delete { background: #ff3333; color: #fff; }
</style>
</head>
<body>
<h1>⚡ Administração de Personagens</h1>

<table>
    <tr>
        <th>Jogador</th>
        <th>Personagem</th>
        <th>Classe</th>
        <th>Level</th>
        <th>Exp</th>
        <th>HP</th>
        <th>Moedas</th>
        <th>Arma</th>
        <th>Escudo</th>
        <th>Capacete</th>
        <th>Armadura</th>
        <th>Luva</th>
        <th>Calça</th>
        <th>Ações</th>
    </tr>

<?php
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
?>
<tr>
<form action="admin_personagens_update.php" method="post">
    <input type="hidden" name="CharID" value="<?= $row['CharID'] ?>">
    <input type="hidden" name="PlayerID" value="<?= $row['PlayerID'] ?>">
    <td><?= $row['NomeJogador'] ?></td>
    <td><input type="text" name="Name" value="<?= $row['Name'] ?>"></td>
    <td><input type="text" name="Class" value="<?= $row['Class'] ?>"></td>
    <td><input type="number" name="Level" value="<?= $row['Level'] ?>"></td>
    <td><input type="number" name="Exp" value="<?= $row['Exp'] ?>"></td>
    <td><input type="number" name="HP" value="<?= $row['HP'] ?>"></td>
    <td><input type="number" name="MoedaMumu" value="<?= $row['MoedaMumu'] ?>"></td>
    <td><input type="text" name="Arma" value="<?= $row['Arma'] ?>"></td>
    <td><input type="text" name="Escudo" value="<?= $row['Escudo'] ?>"></td>
    <td><input type="text" name="Capacete" value="<?= $row['Capacete'] ?>"></td>
    <td><input type="text" name="Armadura" value="<?= $row['Armadura'] ?>"></td>
    <td><input type="text" name="Luva" value="<?= $row['Luva'] ?>"></td>
    <td><input type="text" name="Calça" value="<?= $row['Calça'] ?>"></td>
    <td>
        <button type="submit" class="btn btn-save">💾 Salvar</button>
        <a href="admin_personagens_delete.php?CharID=<?= $row['CharID'] ?>" class="btn btn-delete">🗑️ Excluir</a>
    </td>
</form>
</tr>
<?php endwhile; ?>

</table>

<a href="dashboard.php" class="btn">⬅️ Voltar</a>
</body>
</html>
