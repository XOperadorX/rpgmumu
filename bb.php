<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Banco Interativo - MoedaMumu</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial, sans-serif; background: #1e1e2f; color: #fff; text-align: center; padding: 30px; }
#saldo { font-size: 2em; margin: 20px 0; color: gold; }
canvas { background: #2c2c3c; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
button { padding: 10px 20px; margin: 5px; font-size: 1em; cursor: pointer; border-radius: 5px; background: gold; border: none; color: #1e1e2f; font-weight: bold; }
button:hover { background: #ffd700; }
</style>
</head>
<body>
<h1>Banco Interativo</h1>
<div>Saldo atual de MoedaMumu:</div>
<div id="saldo">0</div>
<canvas id="moedaChart" width="600" height="300"></canvas>
<div>
    <button onclick="buyDays(1)">Comprar 1 dia</button>
    <button onclick="buyDays(5)">Comprar 5 dias</button>
    <button onclick="buyDays(10)">Comprar 10 dias</button>
</div>

<script>
let moedaMumu = 0;
let lastUpdate = 0;
let interestRateDaily = 0.05;
const chartData = { labels: [], datasets: [{ label:'Saldo MoedaMumu', data:[], borderColor:'gold', backgroundColor:'rgba(255,215,0,0.2)', fill:true, tension:0.3 }] };

const ctx = document.getElementById('moedaChart').getContext('2d');
const chart = new Chart(ctx, { type:'line', data:chartData, options:{ scales:{ y:{ beginAtZero:true } } } });

// Atualiza saldo em tempo real
function updateSaldo() {
    const now = Math.floor(Date.now()/1000);
    const diffSeconds = now - lastUpdate;
    const interestRatePerSecond = Math.pow(1 + interestRateDaily, 1/86400) - 1;
    const currentSaldo = Math.floor(moedaMumu * Math.pow(1 + interestRatePerSecond, diffSeconds));
    document.getElementById('saldo').innerText = currentSaldo.toLocaleString();

    chart.data.labels.push(new Date().toLocaleTimeString());
    chart.data.datasets[0].data.push(currentSaldo);
    chart.update();
}
setInterval(updateSaldo, 1000);

// Comprar dias
function buyDays(days){
    fetch('backend_moedamumu.php', {
        method:'POST',
        body:new URLSearchParams({ days })
    })
    .then(res => res.json())
    .then(response => {
        if(response.msg){
            alert(response.msg);
        } else {
            moedaMumu = response.MoedaMumu;
            lastUpdate = response.lastUpdateTimestamp;
            interestRateDaily = response.interestRateDaily;
            alert(`Você comprou ${days} dia(s)!`);
        }
        updateButtonState();
    });
}

// Desabilita botões se saldo insuficiente
function updateButtonState(){
    document.querySelectorAll('button').forEach(btn => {
        const days = parseInt(btn.textContent.match(/\d+/)[0]);
        btn.disabled = moedaMumu < days * costPerDay;
    });
}


// Inicializa saldo
fetch('backend_moedamumu.php', { method:'POST' })
    .then(res => res.json())
    .then(response => {
        moedaMumu = response.MoedaMumu;
        lastUpdate = response.lastUpdateTimestamp;
        interestRateDaily = response.interestRateDaily;
    });
</script>
</body>
</html>
