<?php
session_start();
include "../db.php";
//include "../check_ban.php";

if (!isset($_SESSION['PlayerID'])) {
    die("Acesso negado. Faça login.");
}

// --- Consulta dos ativos e seus preços ---
$sql = "SELECT Nome, PrecoBase, VariacaoAtual, UltimaAtualizacao FROM MercadoAtivos ORDER BY Nome";
$stmt = sqlsrv_query($conn, $sql);
$ativos = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $ativos[] = $row;
}


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Admin Mercado RPG - MoedaMumu</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* ======== TEMA FUTURISTA RPG ======== */
body {
    background: radial-gradient(circle at 20% 20%, #0a0a1f, #000);
    color: #00fff2;
    font-family: 'Orbitron', sans-serif;
    margin: 0;
    padding: 0;
    text-align: center;
    overflow-x: hidden;
}
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap');

h1 {
    color: #0ff;
    text-shadow: 0 0 20px #00ffff;
    margin-top: 30px;
}

.container {
    width: 90%;
    margin: 40px auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 25px;
}

.card {
    background: rgba(20, 20, 40, 0.8);
    border: 2px solid #00f2ff;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 0 25px #00ffff33;
    transition: 0.3s;
}
.card:hover {
    transform: scale(1.05);
    box-shadow: 0 0 35px #00ffff99;
}

h2 {
    color: #00ffea;
    margin-bottom: 10px;
}

.price {
    font-size: 1.5em;
    font-weight: bold;
}
.positive { color: #00ff66; }
.negative { color: #ff0055; }

button {
    background: linear-gradient(90deg, #00f6ff, #0066ff);
    border: none;
    color: white;
    padding: 8px 15px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    font-family: 'Orbitron';
    transition: 0.3s;
}
button:hover {
    transform: scale(1.1);
    box-shadow: 0 0 10px #00ffff;
}

#grafico {
    margin-top: 60px;
    width: 90%;
    height: 400px;
}
nav {
    background: rgba(0,255,255,0.1);
    padding: 10px;
    border-bottom: 1px solid #00ffff55;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
nav a {
    color: #00ffff;
    text-decoration: none;
    margin: 0 10px;
    font-weight: bold;
}
nav a:hover {
    text-shadow: 0 0 10px #00ffff;
}
</style>
</head>
<body>

<nav>
    <div><strong>💎 Painel Admin - Mercado MoedaMumu</strong></div>
    <div>
        <a href="dashboard.php">⬅️ Voltar</a>
        <a href="trade.php">💰 Trade</a>
        <button onclick="atualizarMercado()">🔄 Atualizar Preços</button>
    </div>
</nav>

<h1>📊 Mercado Futurista RPG</h1>

<div class="container" id="cards">
    <?php foreach($ativos as $a): ?>
        <div class="card">
            <h2><?= $a['Nome'] ?></h2>
            <p class="price"><?= $a['PrecoBase'] ?> MoedaMumu</p>
            <p class="<?= $a['VariacaoAtual'] >= 0 ? 'positive':'negative' ?>">
                <?= $a['VariacaoAtual'] >= 0 ? '▲':'▼' ?> <?= $a['VariacaoAtual'] ?>%
            </p>
            <p><small>Atualizado: <?= $a['UltimaAtualizacao']->format('H:i') ?></small></p>
        </div>
    <?php endforeach; ?>
</div>

<canvas id="grafico"></canvas>

<script>
let grafico;
const ctx = document.getElementById('grafico').getContext('2d');

// Simulação de gráfico histórico (poderia puxar via AJAX do MercadoHistorico)
const dados = {
    labels: ['1min','2min','3min','4min','5min'],
    datasets: [{
        label: 'Ouro - Histórico de Preço',
        data: [50, 52, 48, 51, 55],
        borderColor: '#00ffff',
        tension: 0.3,
        fill: false,
    }]
};

grafico = new Chart(ctx, {
    type: 'line',
    data: dados,
    options: {
        responsive: true,
        scales: {
            x: { ticks: { color: '#00ffff' } },
            y: { ticks: { color: '#00ffff' } }
        },
        plugins: {
            legend: { labels: { color: '#00ffff' } }
        }
    }
});

function atualizarMercado() {
    fetch('bolsa_ajax.php?acao=atualizar')
        .then(r => r.json())
        .then(data => {
            alert('💹 Mercado atualizado com sucesso!');
            location.reload();
        })
        .catch(err => alert('Erro ao atualizar mercado: ' + err));
}
</script>

</body>
</html>
