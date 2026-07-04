<?php
if (!isset($conn)) {
    include "db.php"; // Garante que a conexão está disponível
}

if (!isset($_SESSION)) {
    session_start();
}

$playerID = $_SESSION['PlayerID'] ?? null;

if ($playerID) {
    $stmt = sqlsrv_query($conn, "SELECT Banido FROM Players WHERE PlayerID = ?", [$playerID]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (!empty($row['Banido']) && $row['Banido'] == 1) {
            die("⛔ Você está banido e não pode acessar o jogo.");
        }
    }
}


// Puxa saldo corrente e investimento
$sql = "SELECT TOP 1 [Corrente], [Investimento] 
        FROM [MumuDB].[dbo].[BankAccounts] 
        WHERE PlayerID = ?";
$params = [$playerID];
$stmt = sqlsrv_query($conn, $sql, $params);
if($stmt === false) { die(print_r(sqlsrv_errors(), true)); }

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$saldoCorrente = floatval($row['Corrente'] ?? 0);
$investimento = floatval($row['Investimento'] ?? 0);

// Histórico persistente (últimas 50 transações)
$sqlHist = "SELECT TOP 50 [Hora], [Acao] FROM [MumuDB].[dbo].[TradeHistory] 
            WHERE PlayerID = ? ORDER BY Hora DESC";
$paramsHist = [$playerID];
$stmtHist = sqlsrv_query($conn, $sqlHist, $paramsHist);
$historicoDB = [];
if($stmtHist !== false){
    while($r = sqlsrv_fetch_array($stmtHist, SQLSRV_FETCH_ASSOC)){
        $historicoDB[] = [
            'hora' => $r['Hora']->format('H:i:s'),
            'acao' => $r['Acao']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Trade MoedaMumu</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial, sans-serif; background: #1e1e2f; color: #fff; text-align: center; padding: 30px; }
#saldo, #cash, #preco, #total { font-size: 1.6em; margin: 10px 0; color: gold; }
canvas { background: #2c2c3c; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
button { padding: 10px 20px; margin: 5px; font-size: 1em; cursor: pointer; border-radius: 5px; background: gold; border: none; color: #1e1e2f; font-weight: bold; }
button:hover { background: #ffd700; }
#historico { max-width: 700px; margin: 0 auto; text-align: left; background: #2c2c3c; padding: 15px; border-radius: 10px; }
#historico h2 { color: #ffd700; margin-bottom: 10px; }
#historico ul { list-style: none; padding: 0; margin: 0; max-height: 200px; overflow-y: auto; }
#historico li { padding: 5px 0; border-bottom: 1px solid #444; }
nav.top-bar a { margin: 0 10px; color: #ffd700; text-decoration: none; }
nav.top-bar a:hover { text-decoration: underline; }
input#valorMovimento { padding:5px; width:100px; margin-right:5px; border-radius:3px; border:none; }
</style>
</head>
<body>
<div class="container">
    <nav class="top-bar">
	    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>
    </nav>

<h1>Trade MoedaMumu</h1>
<div>Saldo Na Corrente: <span id="saldo"><?=number_format($saldoCorrente,2)?></span></div>
<div>Dinheiro para Investimento: <span id="cash"><?=number_format($investimento,2)?></span></div>
<div>Valorizacao na Conta corrente: <span id="total"><?=number_format($investimento + ($saldoCorrente*10),2)?></span></div>
<div>Preço atual da MoedaMumu: <span id="preco">0.00</span></div>

<canvas id="moedaChart" width="600" height="300"></canvas>

<div>
    <button onclick="trade('buy',1)">Vender 1 investimento</button>
    <button onclick="trade('buy',5)">Vender 5 investimento</button>
    <button onclick="trade('sell',1)">Comprar 1 investimento</button>
    <button onclick="trade('sell',5)">Comprar 5 investimento</button>
</div>

<!-- Depósito e Retirada -->
<div style="margin-top:15px;">
    <input type="number" id="valorMovimento" placeholder="Valor">
    <button onclick="depositar()">Depositar</button>
    <button onclick="retirar()">Retirar</button>
</div>

<div id="historico">
    <h2>Histórico de Compras e Vendas</h2>
    <ul id="listaHistorico"></ul>
</div>

<script>
let moedaMumu = <?=json_encode($saldoCorrente)?>;
let cash = <?=json_encode($investimento)?>;
let precoMoeda = 10.00;
let historico = <?=json_encode($historicoDB)?>;

// Atualiza display
function updateDisplay(){
    document.getElementById('saldo').innerText = moedaMumu.toFixed(2);
    document.getElementById('cash').innerText = cash.toFixed(2);
    document.getElementById('preco').innerText = precoMoeda.toFixed(2);
    document.getElementById('total').innerText = (cash + moedaMumu*precoMoeda).toFixed(2);
}

// Gráfico
const chartData = {
    labels: [],
    datasets: [
        { label:'Preço MoedaMumu', data:[], borderColor:'gold', backgroundColor:'rgba(255,215,0,0.2)', fill:true, tension:0.3 },
        { label:'Saldo MoedaMumu', data:[], borderColor:'cyan', backgroundColor:'rgba(0,255,255,0.2)', fill:true, tension:0.3 }
    ]
};
const ctx = document.getElementById('moedaChart').getContext('2d');
const chart = new Chart(ctx, { type:'line', data:chartData, options:{ scales:{ y:{ beginAtZero:true } } } });

// Atualiza gráfico
function updateChart(){
    const now = new Date().toLocaleTimeString();
    chart.data.labels.push(now);
    chart.data.datasets[0].data.push(precoMoeda);
    chart.data.datasets[1].data.push(moedaMumu);
    if(chart.data.labels.length > 60){ chart.data.labels.shift(); chart.data.datasets.forEach(ds => ds.data.shift()); }
    chart.update();
}

// Flutuação realista
let tendencia = 0, maxTendencia = 0.1, minPreco = 1, maxPreco = 50;
function fluctuatePrice(){
    tendencia += (Math.random() - 0.5)*0.02;
    tendencia = Math.max(Math.min(tendencia, maxTendencia), -maxTendencia);
    precoMoeda += tendencia + (Math.random() - 0.5)*0.1;
    precoMoeda = Math.max(Math.min(precoMoeda, maxPreco), minPreco);
    updateDisplay();
    updateChart();
}

// Atualiza histórico na tela
function updateHistorico(){
    const lista = document.getElementById('listaHistorico');
    lista.innerHTML = '';
    historico.slice(0,50).forEach(item => {
        const li = document.createElement('li');
        li.textContent = `[${item.hora}] ${item.acao}`;
        lista.appendChild(li);
    });
}

// Função trade
function trade(type, amount){
    let msg = '';
    if(type === 'buy'){
        const cost = precoMoeda * amount;
        if(cash >= cost){ cash -= cost; moedaMumu += amount;
            msg = `Comprou ${amount} Moeda(s) por ${cost.toFixed(2)}`;
        } else { alert("Saldo de investimento insuficiente!"); return; }
    } else if(type === 'sell'){
        if(moedaMumu >= amount){ moedaMumu -= amount; cash += precoMoeda * amount;
            msg = `Vendeu ${amount} Moeda(s) por ${(precoMoeda*amount).toFixed(2)}`;
        } else { alert("Você não tem moedas suficientes!"); return; }
    }
    historico.unshift({hora: new Date().toLocaleTimeString(), acao: msg});
    updateHistorico();
    updateDisplay();
    saveBank();
    saveInvestment();
    saveHistory(msg);
}

// Depositar e Retirar
function depositar(){
    const valor = parseFloat(document.getElementById('valorMovimento').value);
    if(isNaN(valor) || valor <= 0){ alert("Digite um valor válido."); return; }
    if(valor > moedaMumu){ alert("Saldo corrente insuficiente!"); return; }
    moedaMumu -= valor;
    cash += valor;
    updateDisplay();
    saveBank();
    saveInvestment();
    const msg = `Depositou ${valor.toFixed(2)} para Investimento`;
    historico.unshift({hora: new Date().toLocaleTimeString(), acao: msg});
    updateHistorico();
    saveHistory(msg);
}

function retirar(){
    const valor = parseFloat(document.getElementById('valorMovimento').value);
    if(isNaN(valor) || valor <= 0){ alert("Digite um valor válido."); return; }
    if(valor > cash){ alert("Saldo de investimento insuficiente!"); return; }
    cash -= valor;
    moedaMumu += valor;
    updateDisplay();
    saveBank();
    saveInvestment();
    const msg = `Retirou ${valor.toFixed(2)} do Investimento`;
    historico.unshift({hora: new Date().toLocaleTimeString(), acao: msg});
    updateHistorico();
    saveHistory(msg);
}

// Salvar saldo corrente
function saveBank(){
    const xhr = new XMLHttpRequest();
    xhr.open("POST","save_bank.php",true);
    xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhr.send("corrente="+encodeURIComponent(moedaMumu));
}

// Salvar investimento
function saveInvestment(){
    const xhr = new XMLHttpRequest();
    xhr.open("POST","save_investment.php",true);
    xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhr.send("investimento="+encodeURIComponent(cash));
}

// Salvar histórico
function saveHistory(acao){
    const xhr = new XMLHttpRequest();
    xhr.open("POST","save_history.php",true);
    xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhr.send("acao="+encodeURIComponent(acao));
}

// Inicializa
updateDisplay();
updateChart();
updateHistorico();
setInterval(fluctuatePrice,1000);
</script>
</body>
</html>
