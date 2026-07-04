<?php
session_start();
include "db.php";
if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado.");
}
$playerID = $_SESSION['PlayerID'];

// ===== 6️⃣ Histórico =====
$historyStmt = sqlsrv_query($conn, "
SELECT TOP 10 Tipo, Valor, Data 
FROM BankHistory WHERE PlayerID=? ORDER BY Data DESC", [$playerID]);
$history = [];
if($historyStmt !== false){
    while($rowHist = sqlsrv_fetch_array($historyStmt, SQLSRV_FETCH_ASSOC)){
        $history[] = $rowHist;
    }
}
?>


<?php
session_start();
include "db.php";         // conexão com SQL Server
include "check_ban.php";  // protege a página

if(!isset($_SESSION['PlayerID'])){
    die("❌ Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// ===== 1️⃣ Pegar dados da poupança =====
$stmt = sqlsrv_query($conn, "SELECT Poupanca, LastUpdate FROM Bank WHERE PlayerID = ?", [$playerID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

$poupanca = floatval($row['Poupanca'] ?? 0);
$lastUpdate = $row['LastUpdate'] ?? new DateTime();

// ===== 2️⃣ Calcular diferença de dias =====
$now = new DateTime();
if($lastUpdate instanceof DateTime === false){
    $lastUpdate = new DateTime(); // caso esteja nulo
}
$diffDays = floor(($now->getTimestamp() - $lastUpdate->getTimestamp()) / 86400); // segundos / dia

// ===== 3️⃣ Definir taxa de juros =====
$taxaJuros = 0.02; // 2% ao dia

$juros = 0;
if($diffDays > 0 && $poupanca > 0){
    $juros = $poupanca * $taxaJuros * $diffDays;
    $poupanca += $juros;

    // ===== 4️⃣ Atualizar banco =====
    sqlsrv_query($conn, "UPDATE Bank SET Poupanca = ?, LastUpdate = ? WHERE PlayerID = ?", [$poupanca, $now, $playerID]);
}

// ===== 5️⃣ Mostrar resultados =====
echo "<h2>💰 Saldo da Poupança</h2>";
echo "Saldo atual: <b>$poupanca Moedas Mumu</b><br>";
if($juros > 0){
    echo "Você ganhou <b>$juros Moedas Mumu</b> de juros em $diffDays dia(s).";
} else {
    echo "Nenhum rendimento disponível no momento. Deposite moedas para render juros!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Banco Mumu RPG</title>
<style>
body { background:#1c1c1c; color:#fff; font-family:Arial,sans-serif; text-align:center; margin:0; padding:0;}
.container { max-width:600px; margin:20px auto; }
h1 { color:#ffcc00; }
.card { background:#2b2b2b; padding:20px; border-radius:10px; margin:20px 0; box-shadow:0 0 10px #000; }
input[type=number] { width:80px; padding:5px; border-radius:5px; border:none; text-align:center; margin-right:5px;}
button { padding:8px 15px; border-radius:5px; border:none; cursor:pointer; margin:5px; background:#ffcc00; color:#000; font-weight:bold; transition:0.2s;}
button:hover { background:#ffdd33; }
table { width:100%; border-collapse:collapse; margin:20px 0; text-align:center;}
th, td { border:1px solid #555; padding:8px; }
th { background:#333; color:#ffcc00; }
.msg { color:#00ff00; font-weight:bold; margin:10px 0;}
.juros { color:#00ff88; font-weight:bold; margin:10px 0;}
.btn { display:inline-block; margin:10px; padding:10px 20px; border-radius:5px; text-decoration:none; color:#fff; background:#444; }
.btn:hover { background:#ffcc00; color:#000; }

/* Moeda animada RPG */
@keyframes pularMoedaRPG {
    0% { transform: translate(0,0) rotate(0deg) scale(1); opacity:1; }
    50% { transform: translate(var(--x), -40px) rotate(var(--r)) scale(var(--s)); opacity:1; }
    100% { transform: translate(var(--x), -80px) rotate(calc(var(--r)*2)) scale(var(--s)); opacity:0; }
}
.moedaAnim {
    position: absolute;
    color: #ffcc00;
    font-weight: bold;
    font-size: 16px;
    pointer-events: none;
    animation: pularMoedaRPG 1.2s ease-out forwards;
    z-index: 9999;
}

/* Partículas mágicas */
.particulaRPG {
    position: absolute;
    width: 6px;
    height: 6px;
    background: radial-gradient(circle, #ffcc00 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    animation: brilharRPG 0.8s ease-out forwards;
    z-index: 9998;
    opacity: 0.8;
}
@keyframes brilharRPG {
    0% { transform: translate(0,0) scale(1); opacity:0.8; }
    50% { transform: translate(var(--x), -30px) scale(var(--s)); opacity:1; }
    100% { transform: translate(var(--x), -60px) scale(var(--s)); opacity:0; }
}
</style>
</head>
<body>
<div class="container">
<nav style="display:flex; justify-content:center; gap:10px; margin:20px;">
    <a href="dashboard.php" class="btn">⬅️ Voltar</a>
    <form method="post"><button class="btn" name="refresh">🔄 Atualizar</button></form>
</nav>

<h1>🏦 Banco Mumu RPG</h1>

<div id="mensagens"></div>

<table>
<tr><th>💰 MoedaMumu</th><th>Corrente</th><th>Poupança</th><th>Pix</th><th>Real</th></tr>
<tr>
<td id="saldo">0.00</td>
<td id="corrente">0.00</td>
<td id="poupanca">0.00</td>
<td id="pix">0.00</td>
<td id="real">0.00</td>
</tr>
</table>

<div class="card">
<h2>Operações Financeiras</h2>

<div>
<label>Mumu → Corrente:</label><br>
<input id="mumuInput" type="number" min="1">
<button onclick="transferir('mumuToCorrente', parseFloat(document.getElementById('mumuInput').value))">Transferir (-5)</button>
</div>

<div>
<label>Corrente → Poupança:</label><br>
<input id="correnteInput" type="number" min="1">
<button onclick="transferir('correnteToPoupanca', parseFloat(document.getElementById('correnteInput').value))">Transferir (-10)</button>
</div>

<div>
<label>Poupança → Pix:</label><br>
<input id="poupancaInput" type="number" min="1">
<button onclick="transferir('poupancaToPix', parseFloat(document.getElementById('poupancaInput').value))">Transferir (-15)</button>
</div>

<div>
<label>Pix → Real:</label><br>
<input id="pixInput" type="number" min="1">
<button onclick="transferir('pixToReal', parseFloat(document.getElementById('pixInput').value))">Converter (-20)</button>
</div>
</div>

<h2>📜 Histórico (últimos 10)</h2>
<table>
<tr><th>Tipo</th><th>Valor</th><th>Data</th></tr>
<?php if(!empty($history)): ?>
<?php foreach($history as $h): ?>
<tr>
<td><?= htmlspecialchars($h['Tipo']) ?></td>
<td><?= number_format($h['Valor'],2) ?></td>
<td><?= $h['Data'] instanceof DateTime ? $h['Data']->format('d/m/Y H:i') : htmlspecialchars($h['Data']) ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="3">Nenhum histórico encontrado.</td></tr>
<?php endif; ?>
</table>
</div>
</div>



<script>
// Mensagem temporária
function mostrarMsg(texto, classe='msg'){
    const div = document.createElement('p');
    div.className = classe;
    div.textContent = texto;
    document.getElementById('mensagens').prepend(div);
    setTimeout(()=>div.remove(),5000);
}

// Moedas + partículas
function mostrarMoedaAnim(valor){
    const saldoEl = document.getElementById('saldo');
    const rect = saldoEl.getBoundingClientRect();
    for(let i=0;i<valor;i++){
        // Moeda
        const span = document.createElement('span');
        span.className='moedaAnim';
        span.textContent='+1 💰';
        const startX = rect.left + Math.random()*50;
        const startY = rect.top + Math.random()*20;
        span.style.left = startX + 'px';
        span.style.top = startY + 'px';
        span.style.setProperty('--x', (Math.random()*60-30)+'px');
        span.style.setProperty('--r', (Math.random()*360-180)+'deg');
        span.style.setProperty('--s', (Math.random()*0.5+0.8));
        document.body.appendChild(span);
        setTimeout(()=>span.remove(), 1200 + i*100);

        // Partículas
        const particulasQtd = 5;
        for(let j=0;j<particulasQtd;j++){
            const p = document.createElement('div');
            p.className='particulaRPG';
            const px = startX + Math.random()*20-10;
            const py = startY + Math.random()*20-10;
            p.style.left = px+'px';
            p.style.top = py+'px';
            p.style.setProperty('--x', (Math.random()*60-30)+'px');
            p.style.setProperty('--s', (Math.random()*0.5+0.5));
            document.body.appendChild(p);
            setTimeout(()=>p.remove(), 800 + j*50);
        }
    }
}

// Atualiza conta e histórico
function atualizarConta(){
    fetch('api_banco.php')
    .then(res=>res.json())
    .then(data=>{
        document.getElementById('saldo').textContent = Number(data.saldo).toFixed(2);
        document.getElementById('corrente').textContent = Number(data.corrente).toFixed(2);
        document.getElementById('poupanca').textContent = Number(data.poupanca).toFixed(2);
        document.getElementById('pix').textContent = Number(data.pix).toFixed(2);
        document.getElementById('real').textContent = Number(data.real).toFixed(2);

        if(data.juros>0){
            mostrarMsg(`💰 +${data.juros} MoedaMumu adicionada por juros!`,'juros');
            mostrarMoedaAnim(data.juros);
        }

        // Histórico
        fetch('api_historico.php')
        .then(res=>res.json())
        .then(hist=>{
            const tabela = document.getElementById('historico');
            tabela.innerHTML='<tr><th>Tipo</th><th>Valor</th><th>Data</th></tr>';
            hist.forEach(h=>{
                const tr=document.createElement('tr');
                tr.innerHTML=`<td>${h.Tipo}</td><td>${Number(h.Valor).toFixed(2)}</td><td>${h.Data}</td>`;
                tabela.appendChild(tr);
            });
        });
    });
}

// Transferências AJAX
function transferir(tipo, valor){
    if(isNaN(valor) || valor<=0){ mostrarMsg('Valor inválido','msg'); return; }
    fetch('api_transfer.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({tipo,valor})
    })
    .then(res=>res.json())
    .then(data=>{
        atualizarConta();
        if(data.msg) mostrarMsg(data.msg,'msg');
    });
}

atualizarConta();
setInterval(atualizarConta,60000);
</script>



</body>
</html>
