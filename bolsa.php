<?php
if (!isset($conn)) { include "db.php"; }
if (!isset($_SESSION)) { session_start(); }

$playerID = $_SESSION['PlayerID'] ?? null;
if(!$playerID){ die("⛔ Faça login."); }

// Saldo e carteira
$stmt = sqlsrv_query($conn,"SELECT MoedaMumu, CarteiraJSON FROM Players WHERE PlayerID=?", [$playerID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$moeda = $row['MoedaMumu'] ?? 500;
$carteira = !empty($row['CarteiraJSON']) ? json_decode($row['CarteiraJSON'],true) : [];

// Ativos
$stmtAtivos = sqlsrv_query($conn,"SELECT AtivoID, Nome, PrecoAtual, UltimaVariacao FROM Ativos ORDER BY AtivoID ASC");
$ativos = [];
while($row = sqlsrv_fetch_array($stmtAtivos, SQLSRV_FETCH_ASSOC)){
    $ativos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>📈 Bolsa RPG Futurista</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{background:#0a0a0a;color:#fff;font-family:'Orbitron',sans-serif;padding:2em;}
h1{text-align:center;color:#ffd700;text-shadow:0 0 15px #ff0;margin-bottom:1em;}
.moedas{text-align:center;font-size:1.5em;color:#ff9900;font-weight:bold;margin-bottom:1em;transition: all 0.3s ease;}
.ativos{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5em;}
.ativo{background:#111;border:2px solid rgba(255,215,0,0.3);border-radius:1em;padding:1.5em;text-align:center;position:relative;overflow:hidden;transition: transform 0.2s, box-shadow 0.3s;}
.ativo:hover{transform:translateY(-0.3em);box-shadow:0 0 1.5em rgba(255,215,0,0.5);}
.ativo h3{color:#ffd700;margin:0.5em 0;font-size:1.5em;}
input[type=number]{width:4em;text-align:center;border-radius:0.5em;padding:0.4em;border:1px solid #555;margin:0.5em 0;}
button{padding:0.6em 1em;margin:0.25em;border:none;border-radius:0.5em;cursor:pointer;font-weight:bold;transition:0.3s;}
.buy{background:linear-gradient(45deg,#28a745,#34d058);color:white;box-shadow:0 0 0.5em rgba(40,167,69,0.6);}
.buy:hover{box-shadow:0 0 1em rgba(40,167,69,0.9);}
.sell{background:linear-gradient(45deg,#dc3545,#ff4b5c);color:white;box-shadow:0 0 0.5em rgba(220,53,69,0.6);}
.sell:hover{box-shadow:0 0 1em rgba(220,53,69,0.9);}
.msg-ativo{font-size:1em;font-weight:bold;position:absolute;top:0.5em;left:50%;transform:translateX(-50%);opacity:0;transition: opacity 0.5s ease;}
.seta{position:absolute;top:10px;right:10px;font-size:1.5em;opacity:0;pointer-events:none;}
.moeda-flutua{position:absolute;font-size:1.2em;animation:moedaAnim 1s ease forwards;pointer-events:none;}
@keyframes moedaAnim{0%{transform:translateY(0);opacity:1;}50%{transform:translateY(-30px);opacity:0.8;}100%{transform:translateY(-60px);opacity:0;}}
table{border-collapse:collapse;width:100%;max-width:800px;margin:1em auto;color:#fff;}
th,td{padding:8px;text-align:center;}
th{background:#222;}
td.compra{color:#4caf50;font-weight:bold;}
td.venda{color:#ff4b5c;font-weight:bold;}
#historico-table tbody tr { transition: background 0.5s, transform 0.3s; }
#historico-table tbody tr.compra { background: rgba(76,175,80,0.2); color:#4caf50; }
#historico-table tbody tr.venda { background: rgba(220,53,69,0.2); color:#ff4b5c; }
#historico-table tbody tr.fade-in { animation: fadeIn 0.5s ease forwards; }
@keyframes fadeIn { from { opacity:0; transform:translateY(-10px);} to {opacity:1; transform:translateY(0);} }
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
<!-- BOTÕES DE NAVEGAÇÃO -->

<nav>
    <div style="margin-top:25px;">
	

	    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>
    </div>
</nav>

<h1>📈 Bolsa RPG Futurista</h1>
<p class="moedas">💰 Saldo: <span id="saldo"><?= $moeda ?></span> moedas</p>

<div class="ativos">
<?php foreach($ativos as $a):
$qtd = $carteira[$a['Nome']] ?? 0;
?>
<div class="ativo" data-nome="<?= $a['Nome'] ?>">
    <h3><?= $a['Nome'] ?></h3>
    <p>Preço: <span class="preco"><?= $a['PrecoAtual'] ?></span> moedas</p>
    <p>Você possui: <span class="qtd"><?= $qtd ?></span></p>
    <input type="number" class="quantidade" value="1" min="1">
    <br>
    <button class="buy">Comprar</button>
    <button class="sell">Vender</button>
    <canvas class="grafico" width="220" height="100"></canvas>
    <div class="msg-ativo"></div>
    <div class="seta"></div>
</div>
<?php endforeach; ?>
</div>

<h2 style="text-align:center;margin-top:2em;color:#ffd700;">📝 Últimas 10 Transações</h2>
<table id="historico-table">
<thead>
<tr>
<th>Data/Hora</th>
<th>Ação</th>
<th>Ativo</th>
<th>Qtd</th>
<th>Preço Unitário</th>
<th>Total</th>
</tr>
</thead>
<tbody></tbody>
</table>

<script>
const charts = {};
document.querySelectorAll('.ativo').forEach(div=>{
    const nome = div.dataset.nome;
    const ctx = div.querySelector('.grafico').getContext('2d');
    charts[nome] = new Chart(ctx,{
        type:'line',
        data:{labels:[],datasets:[{label:nome,data:[],borderColor:'#ffd700',backgroundColor:'rgba(255,215,0,0.2)',tension:0.3,pointRadius:0}]},
        options:{responsive:false,plugins:{legend:{display:false}},scales:{x:{display:false},y:{beginAtZero:true}}}
    });
});

function mostrarSeta(div,variacao){
    const s = div.querySelector('.seta');
    if(variacao>0){ s.innerText='⬆️'; s.className='seta up'; }
    else if(variacao<0){ s.innerText='⬇️'; s.className='seta down'; }
    else{s.innerText=''; s.className='seta';}
}

function flutuarMoeda(el,sinal,qtd){
    for(let i=0;i<Math.min(qtd,10);i++){
        const m=document.createElement('span'); 
        m.className='moeda-flutua'; 
        m.innerText=sinal+'💰';
        document.body.appendChild(m);
        const r=el.getBoundingClientRect();
        m.style.left=r.left+r.width/2+'px'; 
        m.style.top=r.top+'px';
        setTimeout(()=>m.remove(),1000);
    }
}

function atualizarHistorico(){
    fetch('historico_bolsa.php')
        .then(r=>r.json())
        .then(data=>{
            if(data.error){ console.warn(data.error); return; }
            const tbody=document.querySelector('#historico-table tbody');
            tbody.innerHTML='';
            data.tabela.forEach(h=>{
                const cls=h.acao.toLowerCase()==='compra'?'compra':'venda';
                const tr=document.createElement('tr');
                tr.className = cls + ' fade-in';
                tr.innerHTML=`<td>${h.datahora}</td><td class="${cls}">${h.acao}</td><td>${h.item}</td><td>${h.qtd}</td><td>${h.preco_unit}</td><td>${h.total}</td>`;
                tbody.appendChild(tr);
            });
            for(const ativo in data.grafico){
                if(!charts[ativo]) continue;
                const dataset=data.grafico[ativo];
                charts[ativo].data.labels=dataset.map((d,i)=>i+1);
                charts[ativo].data.datasets[0].data=dataset.map(d=>d.preco);
                charts[ativo].update();
            }
        });
}

function transacao(acao,ativo,quantidade){
    quantidade=Math.max(1,parseInt(quantidade));
    fetch('bolsa_ajax.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({acao,ativo,quantidade})
    }).then(r=>r.json()).then(data=>{
        if(data.error){ alert(data.error); return; }
        const div=document.querySelector(`[data-nome="${ativo}"]`);
        document.getElementById('saldo').innerText=data.novo_saldo;
        div.querySelector('.qtd').innerText=data.nova_qtd;
        div.querySelector('.preco').innerText=data.novo_preco;
        charts[ativo].data.labels.push(charts[ativo].data.labels.length+1);
        charts[ativo].data.datasets[0].data.push(data.novo_preco);
        if(charts[ativo].data.labels.length>20){ charts[ativo].data.labels.shift(); charts[ativo].data.datasets[0].data.shift(); }
        charts[ativo].update();
        div.querySelector('.msg-ativo').innerText=data.msg;
        div.querySelector('.msg-ativo').style.color=data.variacao>0?'#4caf50':'#ff4b5c';
        div.querySelector('.msg-ativo').style.opacity=1;
        setTimeout(()=>{div.querySelector('.msg-ativo').style.opacity=0;},2000);
        mostrarSeta(div,data.variacao);
        flutuarMoeda(div,data.variacao>0?'+':'-',quantidade);
        atualizarHistorico();
    });
}

document.querySelectorAll('.ativo').forEach(div=>{
    const nome=div.dataset.nome;
    const input=div.querySelector('.quantidade');
    div.querySelector('.buy').addEventListener('click',()=>transacao('comprar',nome,input.value));
    div.querySelector('.sell').addEventListener('click',()=>transacao('vender',nome,input.value));
});

// Atualiza preços com variação e histórico
setInterval(()=>fetch('atualiza_precos.php').then(r=>r.json()).then(()=>{
    document.querySelectorAll('.ativo').forEach(div=>{
        const nome=div.dataset.nome;
        const preco = parseInt(div.querySelector('.preco').innerText);
        fetch('atualiza_precos.php')
            .then(r=>r.json())
            .then(data=>{
                const novo_preco = data.grafico[nome][0].preco;
                const variacao = novo_preco - preco;
                div.querySelector('.preco').innerText = novo_preco;
                mostrarSeta(div, variacao);
                if(charts[nome]){
                    charts[nome].data.labels.push(charts[nome].data.labels.length+1);
                    charts[nome].data.datasets[0].data.push(novo_preco);
                    if(charts[nome].data.labels.length>20){
                        charts[nome].data.labels.shift();
                        charts[nome].data.datasets[0].data.shift();
                    }
                    charts[nome].update();
                }
            });
    });
    atualizarHistorico();
}),5000);

atualizarHistorico();
</script>
</body>
</html>
