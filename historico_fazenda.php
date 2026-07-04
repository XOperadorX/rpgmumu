<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    die("⛔ Faça login primeiro.");
}

$playerID = $_SESSION['PlayerID'];

function safeQuery($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo "<pre>❌ Erro SQL:\n" . print_r(sqlsrv_errors(), true) . "</pre>";
        exit;
    }
    return $stmt;
}

// 🔹 Busca o histórico do jogador
$stmt = safeQuery($conn, "
    SELECT TOP 50 Acao, NomeFruta, Quantidade, DataRegistro
    FROM dbo.HistoricoFazenda
    WHERE PlayerID = ?
    ORDER BY DataRegistro DESC
", [$playerID]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>📜 Histórico da Fazenda</title>
<style>
body { font-family: 'Orbitron', Arial, sans-serif; background: #0a0a0a; color: #fff; text-align: center; padding: 20px; }
table { margin: 20px auto; border-collapse: collapse; width: 80%; }
th, td { border: 1px solid #00ffcc; padding: 8px; }
th { background: #00ffcc; color: #000; }
tr:nth-child(even) { background: #111; }
a.btn { background: #00ffcc; color: #000; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; }
a.btn:hover { background: #00ffaa; }
</style>
</head>
<body>

<h1>📜 Histórico da Fazenda</h1>
<a href="fazenda.php" class="btn">⬅ Voltar à Fazenda</a>

<table>
<tr><th>Data</th><th>Ação</th><th>Fruta</th><th>Quantidade</th></tr>
<?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
<tr>
    <td><?= $row['DataRegistro']->format('d/m/Y H:i:s') ?></td>
    <td><?= htmlspecialchars($row['Acao']) ?></td>
    <td><?= htmlspecialchars($row['NomeFruta']) ?></td>
    <td><?= $row['Quantidade'] ?></td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
