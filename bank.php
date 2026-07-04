
<?php
session_start();
include "db.php";
include "check_ban.php";

if(!isset($_SESSION['PlayerID'])){
    die("❌ Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];
$msg = '';

/* ===== 1️⃣ Pega saldo de MoedaMumu ===== */
$sqlPlayer = "SELECT MoedaMumu FROM Players WHERE PlayerID=?";
$stmtPlayer = sqlsrv_query($conn, $sqlPlayer, [$playerID]);
if($stmtPlayer === false){ die("Erro ao buscar saldo."); }
$playerData = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC);
$moedaMumu = $playerData['MoedaMumu'] ?? 0;

/* ===== 2️⃣ Puxa ou cria conta bancária ===== */
$sqlBank = "
IF EXISTS(SELECT 1 FROM BankAccounts WHERE PlayerID = ?)
    SELECT Corrente, Poupanca, Pix, LastUpdate FROM BankAccounts WHERE PlayerID = ?
ELSE
BEGIN
    INSERT INTO BankAccounts (PlayerID, Corrente, Poupanca, Pix, LastUpdate)
    VALUES (?, 0, 0, 0, GETDATE());
    SELECT Corrente=0, Poupanca=0, Pix=0, LastUpdate=GETDATE();
END
";
$stmtBank = sqlsrv_query($conn, $sqlBank, [$playerID, $playerID, $playerID]);
$rowBank = sqlsrv_fetch_array($stmtBank, SQLSRV_FETCH_ASSOC);

/* ===== 3️⃣ Calcula juros da poupança ===== */
$poupanca = floatval($rowBank['Poupanca'] ?? 0);
$lastUpdate = $rowBank['LastUpdate'] ?? new DateTime();
if(!($lastUpdate instanceof DateTime)){
    $lastUpdate = new DateTime();
}

$now = new DateTime();
$diffDays = floor(($now->getTimestamp() - $lastUpdate->getTimestamp()) / 86400);
$taxaJuros = 0.02; // 2% ao dia
$juros = 0;

if($diffDays > 0 && $poupanca > 0){
    $juros = $poupanca * $taxaJuros * $diffDays;
    $poupanca += $juros;
    sqlsrv_query($conn, "UPDATE BankAccounts SET Poupanca=?, LastUpdate=GETDATE() WHERE PlayerID=?", [$poupanca, $playerID]);
}

/* ===== 4️⃣ Lógica dos formulários ===== */
$corrente = floatval($rowBank['Corrente'] ?? 0);
$pix = floatval($rowBank['Pix'] ?? 0);
$updateBank = false;
$taxaTransferencia = 1000; // Valor da taxa fixa

function addHistory($conn, $playerID, $tipo, $valor){
    $sql = "INSERT INTO BankHistory (PlayerID, Tipo, Valor, Data) VALUES (?, ?, ?, GETDATE())";
    sqlsrv_query($conn, $sql, [$playerID, $tipo, $valor]);
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Mumu → Corrente
    if(isset($_POST['mumuToCorrente'])){
        $amount = floatval($_POST['deposit']);
        if($amount <= 0) $msg="❌ Valor inválido!";
        elseif($amount + $taxaTransferencia > $moedaMumu) $msg="❌ MoedaMumu Saldo insuficiente da Taxa! 1000.00";
        else {
            $moedaMumu -= ($amount + $taxaTransferencia);
            $corrente += $amount;
            sqlsrv_query($conn, "UPDATE Players SET MoedaMumu=? WHERE PlayerID=?", [$moedaMumu, $playerID]);
            addHistory($conn, $playerID, "Mumu → Corrente", $amount);
            $msg="✅ Mumu → Corrente realizado! Taxa: $taxaTransferencia";
            $updateBank = true;
        }
    }

    // Corrente → Poupança
    if(isset($_POST['correnteToPoupanca'])){
        $amount = floatval($_POST['deposit']);
        if($amount <= 0) $msg="❌ Valor inválido!";
        elseif($amount + $taxaTransferencia > $corrente) $msg="❌ Corrente Saldo insuficiente da Taxa! 1000.00";
        else {
            $corrente -= ($amount + $taxaTransferencia);
            $poupanca += $amount;
            addHistory($conn, $playerID, "Corrente → Poupança", $amount);
            $msg="✅ Corrente → Poupança realizado! Taxa: $taxaTransferencia";
            $updateBank = true;
        }
    }

    // Poupança → Pix
    if(isset($_POST['poupancaToPix'])){
        $amount = floatval($_POST['deposit']);
        if($amount <= 0) $msg="❌ Valor inválido!";
        elseif($amount + $taxaTransferencia > $poupanca) $msg="❌ PouPanca Saldo insuficiente da Taxa! 1000.00";
        else {
            $poupanca -= ($amount + $taxaTransferencia);
            $pix += $amount;
            addHistory($conn, $playerID, "Poupança → Pix", $amount);
            $msg="✅ Poupança → Pix realizado! Taxa: $taxaTransferencia";
            $updateBank = true;
        }
    }

    // Pix → MoedaMumu
    if(isset($_POST['pixToMumu'])){
        $amount = floatval($_POST['pixAmount']);
        if($amount <= 0) $msg="❌ Valor inválido!";
        elseif($amount + $taxaTransferencia > $pix) $msg="❌ Pix Saldo insuficiente da Taxa! 1000.00";
        else {
            $pix -= ($amount + $taxaTransferencia);
            $moedaMumu += $amount;
            sqlsrv_query($conn, "UPDATE Players SET MoedaMumu=? WHERE PlayerID=?", [$moedaMumu, $playerID]);
            addHistory($conn, $playerID, "Pix → MoedaMumu", $amount);
            $msg="✅ Pix → MoedaMumu realizado! Taxa: $taxaTransferencia";
            $updateBank = true;
        }
    }

    if($updateBank){
        sqlsrv_query($conn, "UPDATE BankAccounts SET Corrente=?, Poupanca=?, Pix=?, LastUpdate=GETDATE() WHERE PlayerID=?",
        [$corrente, $poupanca, $pix, $playerID]);
    }
}

/* ===== 5️⃣ Histórico ===== */
$historyStmt = sqlsrv_query($conn, "SELECT TOP 10 Tipo, Valor, Data FROM BankHistory WHERE PlayerID=? ORDER BY Data DESC", [$playerID]);
$history = [];
if($historyStmt !== false){
    while($rowHist = sqlsrv_fetch_array($historyStmt, SQLSRV_FETCH_ASSOC)){
        $history[] = $rowHist;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Banco Mumu RPG</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* ====== ESTILO GERAL ====== */
body {
    background: radial-gradient(circle at top, #202020 0%, #0f0f0f 100%);
    color: #f5f5f5;
    font-family: "Orbitron", "Segoe UI", Arial, sans-serif;
    text-align: center;
    margin: 0;
    padding: 0;
    animation: fadeInBody 1s ease-in-out;
}
@keyframes fadeInBody {
    from { opacity: 0; transform: scale(1.02); }
    to { opacity: 1; transform: scale(1); }
}

/* ====== CABEÇALHO ====== */
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(30,30,30,0.9);
    padding: 15px 30px;
    box-shadow: 0 0 15px rgba(255,204,0,0.2);
    border-bottom: 2px solid #ffcc00;
    backdrop-filter: blur(6px);
    animation: fadeDown 0.8s ease-in-out;
}
@keyframes fadeDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
nav a, nav button {
    background: none;
    border: none;
    color: #ffcc00;
    font-size: 1rem;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.3s, transform 0.2s;
}
nav a:hover, nav button:hover {
    color: #00ccff;
    transform: scale(1.1);
}

/* ====== TÍTULOS ====== */
h1 {
    color: #ffcc00;
    text-shadow: 0 0 10px rgba(255,204,0,0.8);
    margin-top: 25px;
    letter-spacing: 2px;
    animation: pulseGold 2s infinite alternate;
}
@keyframes pulseGold {
    from { text-shadow: 0 0 5px #ffcc00; }
    to { text-shadow: 0 0 20px #ff9900; }
}
h2 {
    color: #00ccff;
    margin-top: 25px;
    text-shadow: 0 0 8px rgba(0,204,255,0.6);
}

/* ====== CARD PRINCIPAL ====== */
.card {
    background: linear-gradient(160deg, #242424, #181818);
    padding: 25px;
    border-radius: 15px;
    margin: 30px auto;
    width: 90%;
    max-width: 420px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.6);
    border: 1px solid rgba(255,255,255,0.05);
    animation: floatCard 4s ease-in-out infinite;
}
@keyframes floatCard {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

/* ====== FORMULÁRIO ====== */
form { margin-top: 10px; }
label { display: block; margin-bottom: 5px; color: #ccc; }
input[type=number] {
    width: 100px;
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid #555;
    background: #1c1c1c;
    color: #fff;
    text-align: center;
    transition: border 0.3s, background 0.3s, box-shadow 0.3s;
}
input[type=number]:focus {
    border-color: #00ccff;
    background: #2b2b2b;
    box-shadow: 0 0 10px #00ccff55;
    outline: none;
}

/* ====== BOTÕES ====== */
button {
    padding: 10px 15px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    margin: 5px;
    background: linear-gradient(145deg, #ffcc00, #ff8800);
    color: #000;
    font-weight: bold;
    transition: all 0.3s ease;
    box-shadow: 0 0 10px rgba(255,204,0,0.3);
}
button:hover {
    background: linear-gradient(145deg, #ffee55, #ffaa00);
    transform: scale(1.08);
    box-shadow: 0 0 20px rgba(255,204,0,0.7);
}

/* ====== LINKS E BOTÕES GERAIS ====== */
.btn {
    display: inline-block;
    margin: 10px;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    color: #fff;
    background: #333;
    font-weight: bold;
    transition: all 0.3s ease;
}
.btn:hover {
    background: #ffcc00;
    color: #000;
    box-shadow: 0 0 15px #ffcc0077;
}

/* ====== MENSAGENS ====== */
.msg {
    color: #00ff88;
    font-weight: bold;
    margin-top: 15px;
    text-shadow: 0 0 8px #00ff8899;
    animation: glowText 1.5s infinite alternate;
}
@keyframes glowText {
    from { text-shadow: 0 0 5px #00ff88; }
    to { text-shadow: 0 0 20px #00ffaa; }
}

/* ====== RESPONSIVIDADE ====== */
@media (max-width: 600px) {
    nav { flex-direction: column; gap: 10px; }
    .card { width: 95%; padding: 15px; }
    input[type=number] { width: 70px; }
}

/* ====== TABELA HISTÓRICO ====== */
.historico-tabela {
    width: 85%;
    margin: 25px auto;
    border-collapse: collapse;
    text-align: center;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid rgba(255,204,0,0.6);
    box-shadow: 0 0 15px rgba(255,204,0,0.25);
    animation: fadeUp 0.8s ease;
}

.historico-tabela th {
    background: linear-gradient(90deg, #333, #222);
    color: #ffcc00;
    font-weight: bold;
    text-transform: uppercase;
    padding: 12px;
    border-bottom: 2px solid rgba(255,204,0,0.3);
}

.historico-tabela td {
    background: rgba(43,43,43,0.9);
    padding: 10px;
    color: #eee;
    border-top: 1px solid #444;
    border-right: 1px solid #333;
}
.historico-tabela td:last-child {
    border-right: none;
}

.historico-tabela tr:hover td {
    background: rgba(255,204,0,0.05);
    transition: background 0.3s;
}

.historico-tabela td:first-child {
    color: #00ccff;
    font-weight: bold;
}

/* ====== TABELA DE SALDO ====== */
.saldo-tabela {
    width: 90%;
    margin: 25px auto;
    border-collapse: collapse;
    text-align: center;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid rgba(255,204,0,0.6);
    box-shadow: 0 0 15px rgba(255,204,0,0.2);
    animation: fadeUp 0.8s ease;
}
.saldo-tabela th {
    background: linear-gradient(90deg, #333, #222);
    color: #ffcc00;
    padding: 12px;
    text-transform: uppercase;
    border-bottom: 2px solid rgba(255,204,0,0.3);
}
.saldo-tabela td {
    background: rgba(43,43,43,0.9);
    padding: 10px;
    color: #eee;
    border-top: 1px solid #444;
    border-right: 1px solid #333;
}
.saldo-tabela td:last-child {
    border-right: none;
}
.saldo-tabela tr:hover td {
    background: rgba(255,204,0,0.05);
    transition: background 0.3s;
}
.saldo-tabela td:first-child {
    color: #ffcc00;
    font-weight: bold;
    animation: pulseCoin 2s infinite alternate;
}
@keyframes pulseCoin {
    from { text-shadow: 0 0 5px #ffcc00; }
    to { text-shadow: 0 0 15px #ffaa00; }
}
</style>

</head>
<body>
<nav style="display:flex; justify-content:center; align-items:center; margin:20px;">
    <form method="post" style="margin:0; display:flex; gap:15px; justify-content:center; align-items:center;">

        <!-- Link para voltar ao dashboard -->
	    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>

		</form>
</nav>

<h1>🏦 Banco Mumu RPG</h1>

<?php if($juros > 0): ?>
<p class="msg">💹 Você ganhou <b><?= number_format($juros,2) ?></b> MoedaMumu em juros nos últimos <?= $diffDays ?> dia(s)!</p>
<?php endif; ?>

<?php if($msg): ?><p class="msg"><?= htmlspecialchars($msg) ?></p><?php endif; ?>



<h2>💰 Meus Saldos</h2>
<table class="saldo-tabela">
    <tr>
        <th>💰 MoedaMumu</th>
        <th>🏦 Corrente</th>
        <th>💹 Poupança</th>
        <th>📲 Pix</th>
    </tr>
    <tr>
		<td><?= number_format($moedaMumu,2) ?></td>
		<td><?= number_format($corrente,2) ?></td>
		<td><?= number_format($poupanca,2) ?></td>
		<td><?= number_format($pix,2) ?></td>
    </tr>
</table>




<div class="card">
<form method="post">
<label>Mumu → Corrente:</label><br>
<input type="number" name="deposit" min="1" max="<?= intval($moedaMumu) ?>" required>
<button name="mumuToCorrente">Transferir</button>
</form>

<form method="post">
<label>Corrente → Poupança:</label><br>
<input type="number" name="deposit" min="1" max="<?= intval($corrente) ?>" required>
<button name="correnteToPoupanca">Transferir</button>
</form>

<form method="post">
<label>Poupança → Pix:</label><br>
<input type="number" name="deposit" min="1" max="<?= intval($poupanca) ?>" required>
<button name="poupancaToPix">Transferir</button>
</form>

<form method="post">
<label>Pix → MoedaMumu:</label><br>
<input type="number" name="pixAmount" min="1" max="<?= intval($pix) ?>" required>
<button name="pixToMumu">Converter</button>
</form>
</div>

<h2>📊 Evolução da Poupança</h2>
<canvas id="chartPoupanca"></canvas>

<h2>📜 Histórico</h2>

<table class="historico-tabela">
<tr>
    <th>Tipo</th>
    <th>Valor</th>
    <th>Data</th>
</tr>
<?php if(!empty($history)): foreach($history as $h): ?>
<tr>
    <td><?= htmlspecialchars($h['Tipo']) ?></td>
    <td><?= number_format($h['Valor'], 2) ?></td>
    <td><?= $h['Data'] instanceof DateTime ? $h['Data']->format('d/m/Y H:i') : $h['Data'] ?></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="3">Nenhum histórico.</td></tr>
<?php endif; ?>
</table>



<script>
// Simula rendimento de 7 dias com juros compostos
const taxa = 0.02;
let saldoInicial = <?= $poupanca ? $poupanca / pow(1+0.02,7) : 0 ?>;
let dados = [];
let labels = [];
for(let i=0; i<=7; i++){
  labels.push("Dia " + i);
  dados.push((saldoInicial * Math.pow(1+taxa,i)).toFixed(2));
}

new Chart(document.getElementById('chartPoupanca'), {
  type: 'line',
  data: {
    labels: labels,
    datasets: [{
      label: '💹 Saldo Poupança (simulação 7 dias)',
      data: dados,
      borderColor: '#00ff88',
      borderWidth: 2,
      fill: false,
      tension: 0.2
    }]
  },
  options: {
    scales: { y: { beginAtZero: true } },
    plugins: { legend: { labels: { color: '#fff' } } }
  }
});
</script>

</body>
</html>
