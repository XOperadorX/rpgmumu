<?php
session_start();
include "db.php";
include "check_ban.php";

if(!isset($_SESSION['PlayerID'])){
    die("⛔ Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// ==========================
// Nome do personagem
// ==========================
$stmtChar = sqlsrv_query($conn, "SELECT Name FROM dbo.Characters WHERE PlayerID = ?", [$playerID]);
if($stmtChar === false){
    die("Erro ao buscar personagem: " . print_r(sqlsrv_errors(), true));
}
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
$nomeJogador = htmlspecialchars($char['Name'] ?? 'Desconhecido');

// ==========================
// Saldo de moedas
// ==========================
$stmtMoedas = sqlsrv_query($conn, "SELECT MoedaMumu FROM dbo.Players WHERE PlayerID = ?", [$playerID]);
if($stmtMoedas === false){
    die("Erro ao buscar saldo: " . print_r(sqlsrv_errors(), true));
}
$player = sqlsrv_fetch_array($stmtMoedas, SQLSRV_FETCH_ASSOC);
$saldo = intval($player['MoedaMumu'] ?? 0);

// ==========================
// Inventário de Itens
// ==========================
$inventario = [];
$stmtItens = @sqlsrv_query($conn, "SELECT ItemID, Nome, Quantidade, Raridade, Valor, Tipo FROM dbo.Items WHERE PlayerID = ?", [$playerID]);
if($stmtItens === false){
    // Apenas avisa, não para a página
    $inventario = [];
} else {
    while($row = sqlsrv_fetch_array($stmtItens, SQLSRV_FETCH_ASSOC)){
        $inventario[$row['ItemID']] = [
            'nome' => $row['Nome'],
            'qtd' => intval($row['Quantidade']),
            'raridade' => strtolower($row['Raridade'] ?? 'comum'),
            'valor' => intval($row['Valor'] ?? 10),
            'tipo' => $row['Tipo']
        ];
    }
}

// ==========================
// Inventário de Sementes/Frutas
// ==========================
$sementes = [];
$stmtSementes = @sqlsrv_query($conn, "SELECT s.FrutaID, f.Nome, s.Quantidade, f.PrecoSemente, f.PrecoVenda
                                      FROM dbo.Sementes s
                                      JOIN dbo.Frutas f ON s.FrutaID = f.FrutaID
                                      WHERE s.PlayerID = ?", [$playerID]);
if($stmtSementes !== false){
    while($row = sqlsrv_fetch_array($stmtSementes, SQLSRV_FETCH_ASSOC)){
        $sementes[$row['FrutaID']] = $row;
    }
}

// ==========================
// Frutas disponíveis na loja
// ==========================
$frutasLoja = [];
$stmtFrutas = @sqlsrv_query($conn, "SELECT FrutaID, Nome, PrecoCompra, PrecoVenda FROM dbo.Frutas");
if($stmtFrutas !== false){
    while($row = sqlsrv_fetch_array($stmtFrutas, SQLSRV_FETCH_ASSOC)){
        $frutasLoja[$row['FrutaID']] = $row;
    }
}

// ==========================
// Cores por raridade
// ==========================
$cores = ['comum'=>'gray','incomum'=>'green','raro'=>'blue','épico'=>'purple','lendário'=>'orange'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Inventário e Loja - RPGMumu</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Orbitron', Arial, sans-serif; background:#1c1c1c; color:#fff; text-align:center; margin:0; padding:20px; }
#inventario-container, #loja-container { width:90%; margin:20px auto; text-align:left; background:#111; padding:15px; border-radius:10px; box-shadow:0 0 20px #0ff,0 0 40px #0ff inset; }
#inventario-container:hover, #loja-container:hover { box-shadow:0 0 25px #0ff,0 0 50px #0ff inset; }
table { width:100%; border-collapse:collapse; }
th, td { padding:10px; border:1px solid #333; text-align:center; }
th { background: linear-gradient(90deg, #ff00ff, #00ffff); color:#fff; text-transform: uppercase; }
.item-box { cursor:pointer; transition: all 0.2s ease-in-out; }
.item-box:hover { box-shadow:0 0 15px #ff0,0 0 30px #f0f inset; transform:scale(1.05); }
button.vender-btn { background:#e74c3c; color:#fff; border:none; padding:8px 15px; border-radius:5px; cursor:pointer; transition: all 0.2s ease-in-out; }
button.vender-btn:hover { background:#c0392b; transform:scale(1.05); }
#mensagem { margin-top:15px; font-weight:bold; color:#0ff; text-shadow:0 0 5px #0ff; }
a.botao { color:#fff; text-decoration:none; background: linear-gradient(90deg, #3498db, #9b59b6); padding:10px 15px; border-radius:5px; margin:5px; display:inline-block; transition: all 0.2s ease-in-out; }
a.botao:hover { background: linear-gradient(90deg, #2980b9, #8e44ad); transform:scale(1.05); }
.saldo { color:#00ff88; font-weight:bold; }
.bar-container { position: relative; background: rgba(0, 255, 255, 0.05); border: 1px solid #0ff; border-radius: 12px; height: 20px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 5px #0ff inset; }


</style>

<!-- Adicione no <head> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">

<style>
body {
    background-color: #0a0a1f;
    color: #0ff;
    font-family: 'Orbitron', sans-serif;
    margin: 0;
}

@keyframes glow {
    0%, 100% {
        text-shadow: 0 0 5px #0ff, 0 0 10px #0ff, 0 0 20px #0ff, 0 0 40px #0ff;
        box-shadow: 0 0 5px #0ff44d55, 0 0 10px #0ff44d55, 0 0 20px #0ff44d55;
    }
    50% {
        text-shadow: 0 0 10px #0ff, 0 0 20px #0ff, 0 0 30px #0ff, 0 0 50px #0ff;
        box-shadow: 0 0 10px #0ff44d55, 0 0 20px #0ff44d55, 0 0 30px #0ff44d55;
    }
}

.top-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 20px;
    background: linear-gradient(90deg, #0f0f2f, #1a1a4d);
    border-bottom: 2px solid #0ff;
    box-shadow: 0 0 20px #0ff55a33;
}

.top-bar a {
    color: #0ff;
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: 0.3s ease;
    border: 1px solid #0ff44d55;
    box-shadow: 0 0 8px #0ff44d55;
    animation: glow 2s infinite alternate;
}

.top-bar a:hover {
    background-color: #0ff;
    color: #1a1a4d;
    transform: scale(1.1);
    box-shadow: 0 0 20px #0ff, 0 0 30px #0ff;
}

.top-bar i {
    animation: glow 2s infinite alternate;
}

@media (max-width: 768px) {
    .top-bar {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<!-- Menu Futurista com Glow -->
<nav class="top-bar">
	    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>
</nav>


</head>
<body>

<div class="info">
Jogador: <strong><?= $nomeJogador ?></strong><br>
Saldo: <span id="saldo" class="saldo"><?= $saldo ?> 💰 MoedasMumu</span>
</div>

<h1>📦 Inventário de Itens</h1>
<div id="inventario-container">
<?php if(empty($inventario)): ?>
<p style="color:#fff;">Você não possui itens no momento.</p>
<?php else: ?>
<table>
<tr><th>Item</th><th>Quantidade</th><th>Valor</th><th>Ação</th></tr>
<?php foreach($inventario as $id => $d): $cor=$cores[$d['raridade']] ?? 'white'; ?>
<tr style="border-left:5px solid <?=$cor?>">
<td><?=htmlspecialchars($d['nome'])?></td>
<td id="qtd-<?=$id?>"><?= $d['qtd'] ?></td>
<td>💰 <?= $d['valor'] ?></td>
<td>
<button class="vender-btn" onclick="venderItem(<?=$id?>)">Vender</button>
<button class="vender-btn" onclick="trocarItem(<?=$id?>)">Trocar</button>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<h1>🛒 Loja de Frutas/Sementes</h1>
<div id="loja-container">
<?php if(empty($frutasLoja)): ?>
<p style="color:#fff;">Loja indisponível ou sem produtos cadastrados.</p>
<?php else: ?>
<table>
<tr><th>Produto</th><th>Preço Compra</th><th>Preço Venda</th><th>Quantidade</th><th>Ação</th></tr>
<?php foreach($frutasLoja as $f): 
    $qtdInv = $sementes[$f['FrutaID']]['Quantidade'] ?? 0;
?>
<tr>
<td><?=htmlspecialchars($f['Nome'])?></td>
<td>💰 <?= $f['PrecoCompra'] ?></td>
<td>💰 <?= $f['PrecoVenda'] ?></td>
<td id="qtd-fruta-<?=$f['FrutaID']?>"><?= $qtdInv ?></td>
<td>
<button onclick="comprarFruta(<?=$f['FrutaID']?>,<?=$f['PrecoCompra']?>)">Comprar</button>
<button onclick="venderFruta(<?=$f['FrutaID']?>,<?=$f['PrecoVenda']?>)">Vender</button>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div id="mensagem"></div>

<script>
function venderItem(itemID){
    fetch('vender_item.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'itemID='+itemID})
    .then(r=>r.json()).then(res=>{
        const msg=document.getElementById('mensagem');
        if(res.sucesso){
            const qtdEl=document.getElementById('qtd-'+itemID);
            if(res.nova_qtd<=0) qtdEl.parentElement.remove();
            else qtdEl.innerText=res.nova_qtd;
            msg.style.color='#0f0'; msg.innerText='Item vendido! +'+res.moedas+' moedas';
            document.getElementById('saldo').innerText=res.novo_saldo+' 💰 MoedasMumu';
        }else{msg.style.color='#f00'; msg.innerText=res.mensagem;}
    });
}

function comprarFruta(frutaID, preco){
    fetch('comprar_fruta.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'frutaID='+frutaID})
    .then(r=>r.json()).then(res=>{
        const msg=document.getElementById('mensagem');
        if(res.success){
            msg.style.color='#0f0'; msg.innerText=res.message;
            const qtdEl=document.getElementById('qtd-fruta-'+frutaID);
            qtdEl.innerText = (parseInt(qtdEl.innerText)||0)+1;
            document.getElementById('saldo').innerText=res.novo_saldo+' 💰';
        }else{msg.style.color='#f00'; msg.innerText=res.message;}
    });
}

function venderFruta(frutaID, preco){
    fetch('vender_fruta.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'frutaID='+frutaID})
    .then(r=>r.json()).then(res=>{
        const msg=document.getElementById('mensagem');
        if(res.success){
            msg.style.color='#0f0'; msg.innerText=res.message;
            const qtdEl=document.getElementById('qtd-fruta-'+frutaID);
            let novaQtd=parseInt(qtdEl.innerText)-1;
            qtdEl.innerText = novaQtd>0 ? novaQtd : 0;
            document.getElementById('saldo').innerText=res.novo_saldo+' 💰';
        }else{msg.style.color='#f00'; msg.innerText=res.message;}
    });
}

function trocarItem(itemID){
    const alvoID=prompt("Digite o ID do jogador que receberá o item:");
    const qtd=prompt("Quantidade a enviar:");
    if(!alvoID||!qtd) return;
    fetch('trocar_item.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'itemID='+itemID+'&alvoID='+alvoID+'&quantidade='+qtd})
    .then(r=>r.json()).then(res=>{
        const msg=document.getElementById('mensagem');
        if(res.success){
            msg.style.color='#0f0'; msg.innerText=res.message;
            const qtdEl=document.getElementById('qtd-'+itemID);
            let nova=parseInt(qtdEl.innerText)-parseInt(qtd);
            qtdEl.innerText = nova>0 ? nova : 0;
        }else{msg.style.color='#f00'; msg.innerText=res.message;}
    });
}
</script>
</body>
</html>
