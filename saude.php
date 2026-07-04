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

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>🩺 Saúde da Conta - Estilo RPG</title>
<style>
/* Fonte futurista */
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap');

body {
    background: #0a0a0a; /* fundo quase preto */
    color: #fff;
    font-family: 'Orbitron', Arial, sans-serif;
    text-align: center;
    padding: 20px;
}

.container {
    max-width: 600px;
    margin: auto;
}

.card {
    background: #111;
    border: 2px solid #0ff; /* borda neon cyan */
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.5); /* glow futurista */
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 30px rgba(0, 255, 255, 0.8);
}

h1 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 2em;
    text-shadow: 0 0 10px #0ff, 0 0 20px #0ff;
}

.progress-bar {
    width: 100%;
    background: #222;
    border-radius: 10px;
    overflow: hidden;
    height: 30px;
    margin: 10px 0;
    position: relative;
    border: 1px solid #0ff;
}

.progress-fill {
    height: 100%;
    width: 0%;
    text-align: center;
    color: #0ff;
    line-height: 30px;
    font-weight: bold;
    border-radius: 10px;
    transition: width 0.5s;
    box-shadow: 0 0 10px #0ff;
}

button {
    padding: 10px 15px;
    font-size: 1em;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
    background: linear-gradient(90deg, #0ff, #3498db);
    color: #000;
    font-weight: bold;
    text-shadow: 0 0 5px #fff;
    transition: 0.3s;
}
button:hover {
    background: linear-gradient(90deg, #3498db, #0ff);
    box-shadow: 0 0 15px #0ff;
}

.info {
    text-align: left;
    margin-top: 10px;
    color: #0ff;
    text-shadow: 0 0 5px #0ff;
    font-size: 0.9em;
}

</style>
</head>
<body>
<nav style="background:#2c3e50; padding:10px; text-align:left;">
    <a href="dashboard.php" style="
        color:#fff;
        text-decoration:none;
        font-weight:bold;
        padding:8px 15px;
        background:#3498db;
        border-radius:5px;
        transition:0.3s;
    " onmouseover="this.style.background='#2980b9';" onmouseout="this.style.background='#3498db';">
        ⬅ Voltar
    </a>
</nav>
<div class="container">
    <div class="card">
        <h1>🩺 Saúde da Conta</h1>
        <p><strong>Usuário:</strong> <span id="username">Carregando...</span></p>
        <p><strong>MoedaMumu:</strong> <span id="moeda">0</span></p>
        <div class="progress-bar">
            <div id="barraSaude" class="progress-fill">0 dias restantes</div>
        </div>
        <div class="info">
            <p><strong>Conta criada:</strong> <span id="createdAt">--/--/----</span></p>
            <p><strong>Último login:</strong> <span id="lastLogin">--/--/----</span> (<span id="lastLoginIP">---</span>)</p>
            <p><strong>Status:</strong> <span id="isBanned">---</span></p>
        </div>
        <div style="margin-top:10px;">
            <input type="number" id="qtdDias" min="1" value="1" style="width:60px;">
            <button id="comprarDias">💰 Comprar Dias por 2k</button>
        </div>
    </div>
</div>

<script>
// Atualiza barra e infos via AJAX
function atualizarSaude(){
    fetch('saude_status.php')
    .then(res => res.json())
    .then(data=>{
        if(data.error) return;

        document.getElementById('username').textContent = data.username;
        document.getElementById('moeda').textContent = data.moeda;
        document.getElementById('createdAt').textContent = data.createdAt;
        document.getElementById('lastLogin').textContent = data.lastLogin;
        document.getElementById('lastLoginIP').textContent = data.lastLoginIP;
        document.getElementById('isBanned').textContent = data.isBanned ? '❌ Bloqueada' : '✅ Ativa';

        let barra = document.getElementById('barraSaude');
        barra.style.width = data.healthPercent + '%';
        barra.textContent = data.remainingDays + ' dia(s) restantes';

        // cores da barra
        if(data.remainingDays >= 2) barra.style.background = '#2ecc71';
        else if(data.remainingDays == 1) barra.style.background = '#f1c40f';
        else barra.style.background = '#e74c3c';
    })
    .catch(err=>console.error(err));
}

// Compra dias
document.getElementById('comprarDias').addEventListener('click', ()=>{
    let qtd = parseInt(document.getElementById('qtdDias').value);
    if(isNaN(qtd) || qtd<1) qtd = 1;

    let formData = new FormData();
    formData.append('days', qtd);

    fetch('saude_comprar.php',{method:'POST', body:formData})
    .then(res=>res.json())
    .then(data=>{
        alert(data.msg);
        atualizarSaude();
    })
    .catch(err=>console.error(err));
});

atualizarSaude();
setInterval(atualizarSaude, 5000);
</script>

</body>
</html>
