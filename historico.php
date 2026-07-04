<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página

if(!isset($_SESSION['PlayerID'])) die("Acesso negado.");

$playerID = $_SESSION['PlayerID'];

// Busca todos os personagens do jogador
$stmtChars = sqlsrv_query($conn,"SELECT CharID, Name FROM Characters WHERE PlayerID=?", [$playerID]);
$chars = [];
while($row=sqlsrv_fetch_array($stmtChars, SQLSRV_FETCH_ASSOC)){
    $chars[$row['CharID']] = $row['Name'];
}

// Busca histórico
$sql = "SELECT * FROM Historico WHERE CharID IN (".implode(',',array_keys($chars)).") ORDER BY CreatedAt DESC";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Histórico de Batalhas e Gastos</title>
<style>
body{font-family:Arial,sans-serif;background:#f0f2f5;color:#333;padding:20px;}
table{width:100%;border-collapse:collapse;}
th,td{padding:8px;border:1px solid #ccc;text-align:left;}
th{background:#444;color:#fff;}
</style>
</head>
<body>
<h1>📜 Histórico de Batalhas e Gastos</h1>
<table>
<tr><th>Personagem</th><th>Ação</th><th>Moeda</th><th>Data/Hora</th></tr>
<?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
<tr>
<td><?= htmlspecialchars($chars[$row['CharID']] ?? 'Desconhecido') ?></td>
<td><?= htmlspecialchars($row['Action']) ?></td>
<td><?= $row['MoedaChange'] ?></td>
<td><?= $row['CreatedAt']->format("d/m/Y H:i:s") ?></td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
