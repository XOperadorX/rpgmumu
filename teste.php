<?php
session_start();
include "db.php";
include "check_ban.php";

if (!isset($_SESSION['PlayerID'])) {
    die("Acesso negado. Faça login para continuar.");
}

$playerID = $_SESSION['PlayerID'];

// === 1️⃣ Busca personagens do jogador ===
$sqlChars = "SELECT CharID, Name FROM Characters WHERE PlayerID = ?";
$stmtChars = sqlsrv_query($conn, $sqlChars, [$playerID]);

$chars = [];
if ($stmtChars) {
    while ($row = sqlsrv_fetch_array($stmtChars, SQLSRV_FETCH_ASSOC)) {
        $chars[$row['CharID']] = $row['Name'];
    }
}

if (empty($chars)) {
    die("<p>❌ Nenhum personagem encontrado para este jogador.</p>");
}

// === 2️⃣ Monta consulta principal ===
// Pegamos o histórico e o saldo atual de MoedaMumu do jogador via JOIN
$charIDs = implode(',', array_map('intval', array_keys($chars)));
$sqlHist = "
SELECT h.CharID, h.Item, h.MoedaChange, h.CreatedAt, p.MoedaMumu
FROM Historico h
JOIN Players p ON p.PlayerID = h.PlayerID
WHERE h.CharID IN ($charIDs)
ORDER BY h.CreatedAt DESC
";

$stmtHist = sqlsrv_query($conn, $sqlHist);
if ($stmtHist === false) {
    die('Erro ao buscar histórico.<pre>' . print_r(sqlsrv_errors(), true) . '</pre>');
}

// === 3️⃣ Processa histórico ===
$rows = [];
$totalMoeda = 0;
$totalMumu = 0;

while ($row = sqlsrv_fetch_array($stmtHist, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
    $totalMoeda += (int)($row['MoedaChange'] ?? 0);
    $totalMumu  = (int)($row['MoedaMumu'] ?? 0); // saldo atual, não somatório
}

// === Helper para data ===
function fmtDate($val) {
    if ($val instanceof DateTime) return $val->format("d/m/Y H:i:s");
    if (is_string($val) && trim($val) !== '') {
        try { return (new DateTime($val))->format("d/m/Y H:i:s"); }
        catch (Exception $e) { return $val; }
    }
    return '-';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Histórico de Itens e Moedas</title>
<style>
body{font-family:Arial,sans-serif;background:#f5f7fa;color:#333;padding:20px}
table{width:100%;border-collapse:collapse;background:#fff;box-shadow:0 0 10px rgba(0,0,0,.1)}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left}
th{background:#2c3e50;color:#fff}
tr:nth-child(even){background:#f9f9f9}
.moeda.positiva{color:green;font-weight:bold}
.moeda.negativa{color:red;font-weight:bold}
.moedamumu{color:#b8860b;font-weight:bold}
.item{color:#555;font-style:italic}
tfoot td{background:#ecf0f1;font-weight:bold;border-top:2px solid #2c3e50}
.empty{text-align:center;color:#777;padding:20px}
</style>
</head>
<body>
<h1>📜 Histórico de Itens e Moedas</h1>

<table>
<thead>
<tr>
    <th>Personagem</th>
    <th>Item</th>
    <th>Moeda</th>
    <th>Moeda Mumu (saldo atual)</th>
    <th>Data/Hora</th>
</tr>
</thead>
<tbody>
<?php if (empty($rows)): ?>
    <tr><td colspan="5" class="empty">Nenhum histórico encontrado.</td></tr>
<?php else: ?>
    <?php foreach ($rows as $r): 
        $item = $r['Item'] ?? '—';
        $moeda = (int)($r['MoedaChange'] ?? 0);
        $classe = $moeda >= 0 ? 'positiva' : 'negativa';
        $mumu = (int)($r['MoedaMumu'] ?? 0);
        $data = fmtDate($r['CreatedAt'] ?? null);
    ?>
    <tr>
        <td><?= htmlspecialchars($chars[$r['CharID']] ?? 'Desconhecido') ?></td>
        <td class="item"><?= htmlspecialchars($item) ?></td>
        <td class="moeda <?= $classe ?>"><?= ($moeda >= 0 ? '+' : '') . number_format($moeda, 0, ',', '.') ?></td>
        <td class="moedamumu">💰 <?= number_format($mumu, 0, ',', '.') ?></td>
        <td><?= htmlspecialchars($data) ?></td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>

<?php if (!empty($rows)): ?>
<tfoot>
<tr>
    <td colspan="2" style="text-align:right">💵 <strong>Total de Moedas:</strong></td>
    <td class="moeda <?= ($totalMoeda >= 0 ? 'positiva' : 'negativa') ?>">
        <?= ($totalMoeda >= 0 ? '+' : '') . number_format($totalMoeda, 0, ',', '.') ?>
    </td>
    <td class="moedamumu">💰 <?= number_format($totalMumu, 0, ',', '.') ?></td>
    <td></td>
</tr>
</tfoot>
<?php endif; ?>
</table>
</body>
</html>
