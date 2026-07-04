<?php
// ... (mesmo código anterior até pegar os dados do player e conta)

// Calcular tempo até próximo rendimento (6 minutos = 360s)
$now = new DateTime();
$lastUpdate = $account['LastPoupancaUpdate'] ?? $now;
$diffSeconds = $now->getTimestamp() - $lastUpdate->getTimestamp();
$timeLeft = max(0, 360 - $diffSeconds);
$progressPercent = min(100, round(($diffSeconds / 360) * 100));



// ===== Pega histórico (substitua pela versão abaixo) =====
$historyStmt = sqlsrv_query($conn, "SELECT TOP 10 Tipo, Valor, Data FROM BankHistory WHERE PlayerID=? ORDER BY Data DESC", [$playerID]);

$history = [];
if($historyStmt === false){
    // se quiser debugar: $errors = sqlsrv_errors(); error_log(print_r($errors, true));
    // deixa $history como array vazio para evitar warnings
} else {
    while($row = sqlsrv_fetch_array($historyStmt, SQLSRV_FETCH_ASSOC)){
        $history[] = $row;
    }
}



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
</style>
</head>
<body>

<h1>🏦 Banco Mumu RPG</h1>

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

<p>💹 Poupança: <?= $account['Poupanca'] ?> (5% a cada 6 min)</p>
<div class="progress-bar">
    <div class="progress-fill" id="progress"></div>
</div>
<p>⏳ Próximo rendimento em <span id="timer"></span></p>

<hr>

<!-- Formulários -->
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

    <?php if(!empty($history) && is_array($history)): ?>
        <?php foreach($history as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['Tipo'] ?? '') ?></td>
                <td><?= htmlspecialchars($h['Valor'] ?? '') ?></td>
                <td>
                    <?php
                    if(isset($h['Data']) && $h['Data'] instanceof DateTime){
                        echo $h['Data']->format('d/m/Y H:i');
                    } elseif (!empty($h['Data'])) {
                        // caso venha como string
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
// Timer JS
let timeLeft = <?= $timeLeft ?>; // segundos restantes
let progress = <?= $progressPercent ?>;
const progressBar = document.getElementById("progress");
const timerText = document.getElementById("timer");

function updateTimer(){
    if(timeLeft <= 0){
        timerText.textContent = "⏱ Agora! Recarregue a página.";
        progressBar.style.width = "100%";
        return;
    }
    let min = Math.floor(timeLeft / 60);
    let sec = timeLeft % 60;
    timerText.textContent = `${min}:${sec.toString().padStart(2,'0')}`;

    progress = 100 - Math.floor((timeLeft / 360) * 100);
    progressBar.style.width = progress + "%";

    timeLeft--;
    setTimeout(updateTimer, 1000);
}
updateTimer();
</script>
<script>
// Timer JS
let timeLeft = <?= $timeLeft ?>; // segundos restantes
let progress = <?= $progressPercent ?>;
const progressBar = document.getElementById("progress");
const timerText = document.getElementById("timer");

function updateTimer(){
    if(timeLeft <= 0){
        timerText.textContent = "💹 Rendimento aplicado!";
        progressBar.style.width = "100%";

        // Aguarda 2 segundos e recarrega a página
        setTimeout(() => {
            location.reload();
        }, 2000);
        return;
    }

    let min = Math.floor(timeLeft / 60);
    let sec = timeLeft % 60;
    timerText.textContent = `${min}:${sec.toString().padStart(2,'0')}`;

    progress = 100 - Math.floor((timeLeft / 360) * 100);
    progressBar.style.width = progress + "%";

    timeLeft--;
    setTimeout(updateTimer, 1000);
}
updateTimer();
</script>


</body>
</html>
