<?php
session_start();
include "db.php"; // Ajuste conforme sua estrutura

// ===== Verifica login e admin =====
if (!isset($_SESSION['PlayerID'])) {
    header("Location: login.php");
    exit;
}

$playerID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn, "SELECT Username, Role FROM Players WHERE PlayerID = ?", [$playerID]);
if ($stmt === false) die("Erro: " . print_r(sqlsrv_errors(), true));

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row || $row['Role'] !== 'admin') {
    die("⛔ Acesso negado. Apenas administradores podem acessar.");
}

$username = $row['Username'];

// ===== Busca últimos 100 logins =====
$sqlLogs = "SELECT TOP 100 PlayerID, Username, LastLoginIP, LastLoginTime, IsBanned
            FROM Players
            ORDER BY LastLoginTime DESC";
$stmtLogs = sqlsrv_query($conn, $sqlLogs);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Logs de Acesso - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { background:#1c1c1c; color:#fff; font-family:Arial, sans-serif; margin:0; }
header { background:#cc0000; padding:20px; text-align:center; }
header h1 { margin:0; color:#fff; }
nav { background:#222; padding:15px; text-align:center; }
nav a { margin:0 10px; padding:12px 20px; border-radius:5px; background:#444; color:#fff; text-decoration:none; font-weight:bold; display:inline-block; transition:0.3s; }
nav a:hover { background:#666; }
main { padding:20px; overflow-x:auto; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:10px; border:1px solid #444; text-align:center; }
th { background:#cc0000; }
tr:nth-child(even){background:#2a2a2a;}
.status-ok { color:#00ff00; font-weight:bold; }
.status-block { color:#ff3333; font-weight:bold; }
@media(max-width:600px){th, td{font-size:12px; padding:5px;} nav a{padding:8px 12px; margin:5px;}}
</style>
</head>
<body>
<header>
<h1>📜 Logs de Acesso - Bem-vindo <?= htmlspecialchars($username) ?></h1>
</header>
<nav>
<a href="admin_dashboard.php">⬅ Voltar ao Painel</a>
<a href="logout.php">🚪 Sair</a>
</nav>
<main>
<h2>Últimos 100 Logins</h2>
<table>
<tr>
<th>ID</th>
<th>Usuário</th>
<th>IP</th>
<th>Data/Hora</th>
<th>Status</th>
</tr>
<?php if ($stmtLogs): ?>
    <?php while($log = sqlsrv_fetch_array($stmtLogs, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
            <td><?= $log['PlayerID'] ?></td>
            <td><?= htmlspecialchars($log['Username']) ?></td>
            <td><?= $log['LastLoginIP'] ?? '—' ?></td>
            <td>
                <?php 
                if ($log['LastLoginTime'] instanceof DateTime) {
                    echo $log['LastLoginTime']->format("d/m/Y H:i:s");
                } else {
                    echo "—";
                }
                ?>
            </td>
            <td>
                <?php if ($log['IsBanned']): ?>
                    <span class="status-block">🚫 Bloqueado</span>
                <?php else: ?>
                    <span class="status-ok">✅ Ativo</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="5">❌ Nenhum log encontrado.</td></tr>
<?php endif; ?>
</table>
</main>
</body>
</html>
