<?php
session_start();
include "db.php";

// Verifica login
if (!isset($_SESSION['PlayerID'])) {
    header("Location: login.php");
    exit;
}

// Puxa dados do jogador logado
$sql = "SELECT Username, Role FROM Players WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row || $row['Role'] !== 'admin') {
    die("⛔ Acesso negado. Apenas administradores podem acessar.");
}

$username = $row['Username'];
$mensagem = "";

// Salva configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverName = $_POST['server_name'] ?? 'Mumu RPG';
    $xpRate = intval($_POST['xp_rate'] ?? 1);
    $dropRate = intval($_POST['drop_rate'] ?? 1);
    $currencyName = $_POST['currency_name'] ?? 'MoedaMumu';
    $registerEnabled = isset($_POST['register_enabled']) ? 1 : 0;

    $sqlUpdate = "UPDATE ServerSettings 
                  SET ServerName=?, XPRate=?, DropRate=?, CurrencyName=?, RegisterEnabled=?";
    $params = [$serverName, $xpRate, $dropRate, $currencyName, $registerEnabled];
    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $params);

    if ($stmtUpdate) {
        $mensagem = "✅ Configurações salvas com sucesso!";
    } else {
        $mensagem = "❌ Erro ao salvar: " . print_r(sqlsrv_errors(), true);
    }
}

// Carrega configurações atuais
$sqlSettings = "SELECT TOP 1 * FROM ServerSettings";
$stmtSettings = sqlsrv_query($conn, $sqlSettings);
$settings = sqlsrv_fetch_array($stmtSettings, SQLSRV_FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Configurações do Servidor - Admin</title>
<style>
body { background:#1c1c1c; color:#fff; font-family:Arial, sans-serif; margin:0; }
header { background:#0077cc; padding:20px; text-align:center; }
header h1 { margin:0; color:#fff; }
nav { background:#222; padding:15px; text-align:center; }
nav a { margin:0 10px; padding:12px 20px; border-radius:5px; background:#444; color:#fff; text-decoration:none; font-weight:bold; display:inline-block; transition:0.3s; }
nav a:hover { background:#666; }
main { padding:30px; }
form { background:#2a2a2a; padding:20px; border-radius:10px; max-width:500px; margin:auto; }
label { display:block; margin-top:15px; text-align:left; }
input[type=text], input[type=number] { width:100%; padding:10px; border:none; border-radius:5px; margin-top:5px; }
input[type=checkbox] { margin-top:10px; }
button { margin-top:20px; padding:12px; border:none; border-radius:5px; background:#ffaa00; cursor:pointer; font-weight:bold; }
button:hover { background:#ffcc33; }
.msg { margin-top:15px; text-align:center; color:#ffcc00; }
</style>
</head>
<body>
<header>
    <h1>⚙️ Configurações do Servidor</h1>
    <p>Admin: <?= htmlspecialchars($username) ?></p>
</header>
<nav>
    <a href="admin_dashboard.php">⬅ Painel</a>
    <a href="admin_bank.php">🏦 Banco</a>
    <a href="admin_logs.php">📜 Logs</a>
    <a href="logout.php">🚪 Sair</a>
</nav>
<main>
    <form method="post">
        <label>Nome do Servidor:
            <input type="text" name="server_name" value="<?= htmlspecialchars($settings['ServerName'] ?? 'Mumu RPG') ?>">
        </label>
        <label>Taxa de XP:
            <input type="number" name="xp_rate" value="<?= htmlspecialchars($settings['XPRate'] ?? 1) ?>">
        </label>
        <label>Taxa de Drop:
            <input type="number" name="drop_rate" value="<?= htmlspecialchars($settings['DropRate'] ?? 1) ?>">
        </label>
        <label>Nome da Moeda:
            <input type="text" name="currency_name" value="<?= htmlspecialchars($settings['CurrencyName'] ?? 'MoedaMumu') ?>">
        </label>
        <label>
            <input type="checkbox" name="register_enabled" <?= (!empty($settings['RegisterEnabled']) ? "checked" : "") ?>>
            Permitir novos cadastros
        </label>
        <button type="submit">💾 Salvar Configurações</button>
        <?php if($mensagem): ?><p class="msg"><?= $mensagem ?></p><?php endif; ?>
    </form>
</main>
</body>
</html>
