<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado.");
}

$playerID = $_SESSION['PlayerID'];

// ==========================
// Nome do personagem (Characters)
// ==========================
$stmtChar = sqlsrv_query($conn, "SELECT Name FROM dbo.Characters WHERE PlayerID = ?", [$playerID]);
if($stmtChar === false) die("Erro ao buscar personagem: " . print_r(sqlsrv_errors(), true));
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
$nomeJogador = htmlspecialchars($char['Name'] ?? 'Desconhecido');

// ==========================
// Saldo de moedas (Players)
// ==========================
$stmtMoedas = sqlsrv_query($conn, "SELECT MoedaMumu FROM dbo.Players WHERE PlayerID = ?", [$playerID]);
if($stmtMoedas === false) die("Erro ao buscar saldo: " . print_r(sqlsrv_errors(), true));
$player = sqlsrv_fetch_array($stmtMoedas, SQLSRV_FETCH_ASSOC);
$saldo = intval($player['MoedaMumu'] ?? 0);

// ==========================
// Histórico de vendas (histvend)
// ==========================
$stmtHist = sqlsrv_query($conn, "
    SELECT TOP 50 Acao, Data
    FROM dbo.histvend
    WHERE PlayerID = ?
    ORDER BY ID DESC
", [$playerID]);
if($stmtHist === false) die("Erro ao buscar histórico: " . print_r(sqlsrv_errors(), true));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Histórico de Vendas - RPGMumu</title>
<style>
body { background:#1c1c1c; color:#fff; font-family:Arial; text-align:center; margin:0; padding:0; }
.container { width:90%; margin:30px auto; background:#111; border-radius:10px; padding:20px; box-shadow:0 0 10px #000; }
h1 { color:#ffd700; text-shadow:0 0 8px #ffd700; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:10px; border:1px solid #333; }
th { background:#333; color:#ffd700; }
tr:nth-child(even) { background:#1e1e1e; }
.info { margin-bottom:20px; font-size:18px; }
.saldo { color:#00ff88; font-weight:bold; }
a.botao, button.botao {
    color:#fff; text-decoration:none; background:#3498db;
    padding:10px 15px; border-radius:5px; margin:5px;
    display:inline-block; border:none; cursor:pointer; font-size:15px;
}
a.botao:hover, button.botao:hover { background:#2980b9; }
button.botao:active { transform:scale(0.98); }
.status {
    margin-top:10px;
    font-size:14px;
    color:#888;
}

/* Links estilo botão */
a.botao { 
    color:#fff; 
    text-decoration:none; 
    background: linear-gradient(90deg, #3498db, #9b59b6); 
    padding:10px 15px; 
    border-radius:5px; 
    margin:5px; 
    display:inline-block; 
    transition: all 0.2s ease-in-out;
}
a.botao:hover { 
    background: linear-gradient(90deg, #2980b9, #8e44ad); 
    transform: scale(1.05);
}
.info { margin-bottom:20px; font-size:18px; }
.saldo { color:#00ff88; font-weight:bold; }
a.botao, button.botao {
    color:#fff; text-decoration:none; background:#3498db;
    padding:10px 15px; border-radius:5px; margin:5px;
    display:inline-block; border:none; cursor:pointer; font-size:15px;
}
</style>
</head>
<body>

<nav>
    <div style="margin-top:25px;">
	
	    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>
		<a href="histvend.php" class="botao">📜 Histórico</a>
        <a href="inventario.php" class="botao">⬅ 🎒 Inventário</a>
        <a href="dung.php" class="botao">🗡️ Masmorra</a>
        <button class="botao" onclick="atualizarHistorico(true)">🔄 Atualizar Histórico</button>
    </div>
</nav>


<div class="container">
    <h1>📜 Histórico de Vendas</h1>

    <div class="info">
        Jogador: <strong><?= $nomeJogador ?></strong><br>
        Saldo atual: <span id="saldo" class="saldo"><?= $saldo ?> 💰 MoedasMumu</span>
    </div>

    <table id="tabela-historico">
        <tr><th>Data</th><th>Ação</th></tr>
        <?php while($row = sqlsrv_fetch_array($stmtHist, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
            <td><?= $row['Data']->format('d/m/Y H:i') ?></td>
            <td><?= htmlspecialchars($row['Acao']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div id="status" class="status"></div>

</div>

<script>
// Atualiza histórico e saldo via AJAX
function atualizarHistorico(manual = false){
    const status = document.getElementById('status');
    if(manual) status.textContent = '🔄 Atualizando...';

    fetch('histvend_atualizar.php')
    .then(r => r.json())
    .then(data => {
        document.getElementById('tabela-historico').innerHTML = data.html;
        document.getElementById('saldo').innerText = data.saldo + ' 💰 MoedasMumu';
        if(manual) status.textContent = '✅ Atualizado manualmente!';
        else status.textContent = '🕒 Atualizado automaticamente às ' + new Date().toLocaleTimeString();
    })
    .catch(() => status.textContent = '⚠️ Erro ao atualizar histórico.');
}

// Atualização automática a cada 30 segundos
setInterval(() => atualizarHistorico(false), 30000);
</script>
</body>
</html>
