<?php
include "db.php";

// Busca todos os jogadores
$sql = "SELECT TOP 1000 PlayerID, Username, PasswordHash, MoedaMumu, CreatedAt, UpdatedAt, LastLoginIP, LastLoginTime, IsBanned, Role, CodigoUsado FROM Players";
$stmt = sqlsrv_query($conn, $sql);

if(!$stmt){
    die("Erro ao consultar jogadores: " . print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Lista de Jogadores</title>
<style>
table { border-collapse: collapse; width: 100%; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; }
th { background:#f4f4f4; }
</style>
</head>
<nav>
<a href="admin_dashboard.php">⬅ Voltar ao Painel</a>
<a href="logout.php">🚪 Sair</a>
</nav>
<body>

<h2>Lista de Jogadores</h2>


<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Senha (hash)</th>
            <th>MoedaMumu</th>
            <th>Criado em</th>
            <th>Atualizado em</th>
            <th>Último IP</th>
            <th>Último login</th>
            <th>Banido</th>
            <th>Role</th>
            <th>Código usado</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
            <td><?= $row['PlayerID'] ?></td>
            <td><?= htmlspecialchars($row['Username']) ?></td>
            <td><?= htmlspecialchars($row['PasswordHash']) ?></td>
            <td><?= $row['MoedaMumu'] ?></td>
            <td><?= $row['CreatedAt'] ? $row['CreatedAt']->format('d/m/Y H:i') : '' ?></td>
            <td><?= $row['UpdatedAt'] ? $row['UpdatedAt']->format('d/m/Y H:i') : '' ?></td>
            <td><?= $row['LastLoginIP'] ?></td>
            <td><?= $row['LastLoginTime'] ? $row['LastLoginTime']->format('d/m/Y H:i') : '' ?></td>
            <td><?= $row['IsBanned'] ? 'Sim' : 'Não' ?></td>
            <td><?= $row['Role'] ?></td>
            <td><?= htmlspecialchars($row['CodigoUsado']) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
