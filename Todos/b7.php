<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];
$msg = '';

// ===== 1️⃣ Puxa saldo de moedas =====
$possibleColumns = ['MoedaMumu', 'Money', 'Coins', 'Zen'];
$moedaMumu = 0;
$stmtPlayer = false;

foreach($possibleColumns as $col){
    $sqlPlayer = "SELECT $col FROM Players WHERE PlayerID=?";
    $stmtPlayer = sqlsrv_query($conn, $sqlPlayer, [$playerID]);

    if($stmtPlayer === false){
        $errors = sqlsrv_errors();
        $columnError = false;
        if(!empty($errors)){
            foreach($errors as $e){
                if(strpos($e['message'], 'Invalid column') !== false){
                    $columnError = true;
                    break;
                }
            }
        }
        if($columnError) continue;
        else { echo "<pre>Erro SQL Players:\n"; print_r($errors); die(); }
    } else {
        $playerData = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC);
        if(isset($playerData[$col])){
            $moedaMumu = $playerData[$col];
            break;
        }
    }
}

if($stmtPlayer === false || $moedaMumu === 0){
    echo "<p style='color:red'>Erro: não foi possível obter saldo de moedas.</p>";
    die();
}

// ===== 2️⃣ Puxa dados da conta bancária =====
$sqlBank = "SELECT Corrente, Poupanca, Pix, Real, LastUpdate FROM BankAccounts WHERE PlayerID=?";
$stmtBank = sqlsrv_query($conn, $sqlBank, [$playerID]);

if($stmtBank === false){
    echo "<pre>Erro SQL BankAccounts:\n";
    print_r(sqlsrv_errors());
    die();
}

$rowBank = sqlsrv_fetch_array($stmtBank, SQLSRV_FETCH_ASSOC);

if(!$rowBank){
    $insertBank = "INSERT INTO BankAccounts (PlayerID, Corrente, Poupanca, Pix, Real, LastUpdate) VALUES (?, 0, 0, 0, 0, GETDATE())";
    $resInsert = sqlsrv_query($conn, $insertBank, [$playerID]);
    if($resInsert === false){
        echo "<pre>Erro ao criar BankAccounts:\n";
        print_r(sqlsrv_errors());
        die();
    }

    $rowBank = [
        'Corrente' => 0,
        'Poupanca' => 0,
        'Pix'      => 0,
        'Real'     => 0,
        'LastUpdate' => new DateTime()
    ];
}

// ===== 3️⃣ Atualiza Corrente com MoedaMumu =====
$resUpdate = sqlsrv_query($conn, "UPDATE BankAccounts SET Corrente=? WHERE PlayerID=?", [$moedaMumu, $playerID]);

// ===== 4️⃣ Cálculo do rendimento da poupança (0,01%) =====
$contaCorrente = $rowBank['Corrente'] ?? 0;
$poupanca      = $rowBank['Poupanca'] ?? 0;
$pix           = $rowBank['Pix'] ?? 0;
$real          = $rowBank['Real'] ?? 0;
$lastUpdate    = $rowBank['LastUpdate'] ?? new DateTime();

if(!($lastUpdate instanceof DateTime)){
    $lastUpdate = new DateTime();
}

$agora = new DateTime();
$diff = $agora->getTimestamp() - $lastUpdate->getTimestamp();
$cycles = floor($diff / 360); // cada ciclo = 6 min

if($cycles > 0 && $poupanca > 0){
    $rendimento = $poupanca * (0.0001 * $cycles); // 0,01% por ciclo
    $poupanca += $rendimento;

    $updatePoupanca = "UPDATE BankAccounts SET Poupanca=?, LastUpdate=GETDATE() WHERE PlayerID=?";
    sqlsrv_query($conn, $updatePoupanca, [$poupanca, $playerID]);

    echo "<p style='color:green'>💹 Rendimento aplicado: +".number_format($rendimento,2,",",".")."</p>";
}

// ===== 5️⃣ Calcula tempo restante para próximo rendimento =====
$now = new DateTime();
$diffSeconds = $now->getTimestamp() - $lastUpdate->getTimestamp();
$timeLeft = max(0, 360 - $diffSeconds);
$progressPercent = min(100, round(($diffSeconds / 360) * 100));

// ===== 6️⃣ Histórico de transações =====
$historyStmt = sqlsrv_query($conn, "SELECT TOP 10 Tipo, Valor, Data FROM BankHistory WHERE PlayerID=? ORDER BY Data DESC", [$playerID]);
$history = [];
if($historyStmt !== false){
    while($rowHist = sqlsrv_fetch_array($historyStmt, SQLSRV_FETCH_ASSOC)){
        $history[] = $rowHist;
    }
}

// ===== 7️⃣ Monta $account =====
$account = [
    'PlayerID'  => $playerID,
    'MoedaMumu' => $moedaMumu,
    'Corrente'  => $contaCorrente,
    'Poupanca'  => $poupanca,
    'Pix'       => $pix,
    'Real'      => $real
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Banco Mumu RPG</title>
<style>
body { background:#1c1c1c; color:#fff; font-family:Arial,sans-serif; text-align:center; }
.card { background:#2b2b2b; padding:20px; border-radius:10px; margin:20px auto; width:420px; box-shadow:0 0 10px #000; }
input[type=number] { width:80px; padding:5px; border-radius:5px; border:none; text-align:center; }
button { padding:10px 15px; border-radius:5px; border:none; cursor:pointer; margin:5px; background:#ffcc00; color:#000; font-weight:bold; }
button:hover { background:#ffdd33; }
h1 { color:#ffcc00; }
table { width:90%; margin:20px auto; border-collapse:collapse; text-align:center; }
th, td { border:1px solid #555; padding:8px; }
th { background:#333; color:#ffcc00; }
.progress-bar { background:#444; border-radius:5px; overflow:hidden; height:20px; margin:10px 0; }
.progress-fill { background:#ffcc00; height:100%; width:<?= $progressPercent ?>%; transition: width 1s linear; }
.msg { color: #00ff00; font-weight:bold; }

.btn { display:inline-block; margin:10px; padding:10px 20px; border-radius:5px; text-decoration:none; color:#fff; background:#444; }
.btn:hover { background:#ffcc00; color:#000; }
</style>
</head>
<body>



<div style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <h1>🏦 Banco Mumu RPG</h1>
    <form method="post" style="margin:0;">
        <button type="submit"class="btn" name="refresh">🔄 Atualizar</button>
		<a href="dashboard.php" class="btn">⬅️ Voltar</a>

    </form>
</div>

<?php if($msg): ?><p class="msg"><?= $msg ?></p><?php endif; ?>



<table>
<tr>
    <th>💰 Moedas Mumu</th>
    <th>🏦 Corrente</th>
    <th>💹 Poupança</th>
    <th>📲 Pix</th>
    <th>💵 Real</th>
</tr>
<tr>
    <td><?= number_format($account['MoedaMumu'],2) ?></td>
    <td><?= number_format($account['Corrente'],2) ?></td>
    <td><?= number_format($account['Poupanca'],2) ?></td>
    <td><?= number_format($account['Pix'],2) ?></td>
    <td><?= number_format($account['Real'],2) ?></td>
</tr>
</table>

<div class="card">
<h2>Conta de <?= $account['PlayerID'] ?></h2>

<p>💹 Poupança: <?= number_format($account['Poupanca'],2) ?> (0,01% a cada 6 min)</p>
<div class="progress-bar">
    <div class="progress-fill" id="progress"></div>
</div>
<p>⏳ Próximo rendimento em <span id="timer"></span></p>

<hr>

<form method="post">
    <label>Mumu → Corrente:</label><br>
    <input type="number" name="deposit" min="1" max="<?= $account['MoedaMumu'] ?>" required>
    <button type="submit" name="mumuToCorrente">Transferir</button>
</form>

<form method="post">
    <label>Corrente → Poupança:</label><br>
    <input type="number" name="deposit" min="1" max="<?= $account['Corrente'] ?>" required>
    <button type="submit" name="correnteToPoupanca">Transferir</button>
</form>

<form method="post">
    <label>Poupança → Pix:</label><br>
    <input type="number" name="deposit" min="1" max="<?= $account['Poupanca'] ?>" required>
    <button type="submit" name="poupancaToPix">Transferir</button>
</form>

<form method="post">
    <label>Pix → Real:</label><br>
    <input type="number" name="pixAmount" min="1" max="<?= $account['Pix'] ?>" required>
    <button type="submit" name="pixToReal">Converter</button>
</form>
</div>

<h2>📜 Histórico (últimos 10)</h2>
<table>
<tr><th>Tipo</th><th>Valor</th><th>Data</th></tr>
<?php if(!empty($history)): ?>
    <?php foreach($history as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h['Tipo'] ?? '') ?></td>
            <td><?= htmlspecialchars($h['Valor'] ?? '') ?></td>
            <td>
                <?php
                if(isset($h['Data']) && $h['Data'] instanceof DateTime){
                    echo $h['Data']->format('d/m/Y H:i');
                } elseif(!empty($h['Data'])){
                    echo htmlspecialchars($h['Data']);
                } else {
                    echo '-';
                }
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr><td colspan="3">Nenhum histórico encontrado.</td></tr>
<?php endif; ?>
</table>

<a href="dashboard.php" style="color:#ffcc00; text-decoration:none;">⬅️ Voltar ao Dashboard</a>

<script>
const progressBar = document.getElementById("progress");
const timerText = document.getElementById("timer");
const totalCycle = 360; // 6 minutos
let lastUpdateTimestamp = <?= $lastUpdate->getTimestamp() ?>;

function updateProgress(){
    const now = Math.floor(Date.now()/1000);
    let elapsed = now - lastUpdateTimestamp;
    let timeLeft = Math.max(0, totalCycle - elapsed);

    let min = Math.floor(timeLeft/60);
    let sec = timeLeft % 60;
    timerText.textContent = `${min}:${sec.toString().padStart(2,'0')}`;

    let percent = Math.min(100, (elapsed/totalCycle)*100);
    progressBar.style.width = percent + "%";

    if(elapsed >= totalCycle){
        timerText.textContent = "💹 Rendimento aplicado!";
        progressBar.style.width = "100%";
        setTimeout(()=>{ location.reload(); }, 2000);
        return;
    }

    requestAnimationFrame(updateProgress);
}
updateProgress();
</script>

</body>
</html>
