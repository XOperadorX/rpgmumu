<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    die("⛔ Faça login primeiro.");
}

$playerID = $_SESSION['PlayerID'];

// ===== Funções auxiliares =====
function safeQuery($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo "<pre>❌ Erro na query:\n$sql\n";
        print_r(sqlsrv_errors());
        echo "</pre>";
        return [];
    }
    $result = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $result[] = $row;
    }
    return $result;
}

// ===== Dados principais =====
$slots = safeQuery($conn, "
    SELECT pf.SlotID, pf.FrutaID, pf.DataPlantio, f.Nome AS Fruta, f.TempoCrescimento
    FROM dbo.PlantacaoFazenda pf
    LEFT JOIN dbo.Frutas f ON pf.FrutaID = f.FrutaID
    WHERE pf.PlayerID = ?", [$playerID]);

$sementes = safeQuery($conn, "
    SELECT s.FrutaID, f.Nome, s.Quantidade, f.PrecoVendaSemente, f.PrecoCompraSemente, f.PrecoSemente, f.PrecoTrocaSemente, f.Raridade
    FROM dbo.Sementes s
    JOIN dbo.Frutas f ON s.FrutaID = f.FrutaID
    WHERE s.PlayerID = ?", [$playerID]);

$frutas = safeQuery($conn, "
    SELECT i.FrutaID, f.Nome, i.Quantidade, f.PrecoVendaFruta, f.PrecoCompraFruta, f.PrecoFruta, f.PrecoTrocaFruta, f.Raridade
    FROM dbo.InventarioFrutas i
    JOIN dbo.Frutas f ON i.FrutaID = f.FrutaID
    WHERE i.PlayerID = ?", [$playerID]);

$todasFrutas = safeQuery($conn, "
    SELECT FrutaID, Nome, PrecoCompraSemente, PrecoVendaSemente, PrecoFruta, PrecoVendaFruta, PrecoTrocaFruta, PrecoTrocaSemente, Raridade, TempoCrescimento
    FROM dbo.Frutas
");

$saldo = safeQuery($conn, "SELECT Poupanca FROM dbo.BankAccounts WHERE PlayerID = ?", [$playerID]);
$poupanca = isset($saldo[0]['Poupanca']) ? number_format($saldo[0]['Poupanca'], 2, ',', '.') : '0,00';

$historico = safeQuery($conn, "
    SELECT TOP 10 Acao, NomeFruta, Quantidade, DataRegistro
    FROM dbo.HistoricoFazenda
    WHERE PlayerID = ?
    ORDER BY DataRegistro DESC", [$playerID]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>🌾 Fazenda - RPG</title>
<style> /* ======== Estilo Geral ======== */ body { font-family: 'Orbitron', sans-serif; background: radial-gradient(circle at top left, #0b0b0b, #0d0d1a 70%); color: #00ffcc; text-align: center; padding: 20px; margin: 0; min-height: 100vh; overflow-x: hidden; } h1, h2 { margin: 10px 0; text-shadow: 0 0 12px #00ffd0, 0 0 25px rgba(0,255,204,0.6); } a, button, select, input { font-family: inherit; } /* ======== Top Bar ======== */ .top-bar { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; background: linear-gradient(90deg,#0b0b0b,#111111); padding: 12px 0; border-bottom: 3px solid #00ffcc; box-shadow: 0 0 15px #00ffcc inset, 0 0 25px rgba(0,255,204,0.6); border-radius: 0 0 20px 20px; } .top-bar a { color: #00ffcc; text-decoration: none; padding: 10px 18px; border: 2px solid #00ffcc; border-radius: 12px; font-weight: bold; font-size: 15px; transition: 0.3s ease; letter-spacing: 0.5px; } .top-bar a:hover { color: #fff; background: linear-gradient(45deg,#00ffcc,#00d4a0); box-shadow: 0 0 15px #00ffcc, 0 0 25px #00d4a0 inset; border-color: #00d4a0; transform: translateY(-2px); } /* ======== Seções e Layout ======== */ .caixa-secao { border: 3px double #00ffcc; border-radius: 20px; padding: 25px; margin: 30px auto; width: 90%; background: rgba(10, 10, 20, 0.9); box-shadow: 0 0 25px rgba(0,255,204,0.3) inset, 0 0 40px rgba(0,255,204,0.2); transition: box-shadow 0.4s ease, transform 0.3s ease; } .caixa-secao:hover { transform: translateY(-5px); box-shadow: 0 0 35px rgba(0,255,204,0.4) inset, 0 0 50px rgba(0,255,204,0.4); } .caixa-secao h2 { text-shadow: 0 0 10px #00ffcc, 0 0 20px #00d4a0; } /* ======== Botões ======== */ .btn, .btn-historico { background: linear-gradient(45deg,#00ffcc,#00d4a0); border: 2px solid #00ffcc; padding: 10px 18px; border-radius: 14px; cursor: pointer; font-weight: bold; color: #0b0b0b; transition: all 0.3s ease; box-shadow: 0 0 12px rgba(0,255,204,0.4); } .btn:hover, .btn-historico:hover { background: linear-gradient(45deg,#00ffd0,#00ffa0); transform: translateY(-2px) scale(1.05); box-shadow: 0 0 20px #00ffcc, 0 0 25px #00d4a0 inset; } /* ======== Tabelas ======== */ table { width: 85%; margin: 15px auto; border-collapse: collapse; border: 2px solid #00ffcc; border-radius: 14px; overflow: hidden; box-shadow: 0 0 20px #00ffcc inset; } th, td { border: 1px solid rgba(0,255,204,0.4); padding: 10px; text-align: center; font-size: 14px; } th { background: linear-gradient(90deg,#0b0b0b,#111111); color: #00ffd0; text-transform: uppercase; letter-spacing: 0.8px; } tr:nth-child(even) { background: rgba(0,255,204,0.05); } /* ======== Slots da Fazenda ======== */ .container { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-top: 25px; } .slot { background: #111; border: 3px double #00ffcc; border-radius: 20px; padding: 15px; height: 180px; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: 0 0 20px rgba(0,255,204,0.6); transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease; } .slot:hover { transform: translateY(-5px) scale(1.04); box-shadow: 0 0 30px #00ffd0, 0 0 45px #00ffcc inset; border-color: #00d4a0; } /* ======== Log / Histórico ======== */ .log { background: #111; border: 2px double #00ffcc; border-radius: 14px; padding: 15px; margin: 15px auto; width: 85%; height: 120px; overflow-y: auto; box-shadow: 0 0 15px #00ffcc inset; } /* ======== Inputs e Selects ======== */ input[type="number"], select { background: #0d0d0d; color: #00ffcc; border: 1px solid #00ffcc; border-radius: 8px; padding: 5px 8px; text-align: center; transition: 0.3s; } input[type="number"]:focus, select:focus { outline: none; box-shadow: 0 0 10px #00ffd0; border-color: #00d4a0; } /* ======== Responsividade ======== */ @media (max-width: 800px) { .top-bar { flex-direction: column; gap: 8px; } .top-bar a { width: 80%; text-align: center; } table { width: 95%; font-size: 13px; } .caixa-secao { width: 95%; padding: 15px; } .slot { height: 160px; } } </style>

</head>
<body>

<nav class="top-bar">
	    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>
</nav>

<h1>🏡 Fazenda RPG - MoedaMumu</h1>
<a href="historico_fazenda.php" class="btn-historico">📜 Histórico Completo</a>
<h2>💰 Saldo da Poupança: 💎 <?= $poupanca ?></h2>

<!-- ==================== COMPRA DE SEMENTES ==================== -->
<div class="caixa-secao">
<h2>🛒 Comprar Semente 🌱</h2>
<select id="compraSemente">
    <option value="">-- Escolha --</option>
    <?php foreach($todasFrutas as $f): ?>
        <option value="<?= $f['FrutaID'] ?>"><?= htmlspecialchars($f['Nome']) ?> (💰 <?= $f['PrecoCompraSemente'] ?? 0 ?>)</option>
    <?php endforeach; ?>
</select>
<button class="btn" onclick="comprarSemente()">Comprar</button>
</div>

<!-- ==================== INVENTÁRIO DE SEMENTES ==================== -->
<div class="caixa-secao">
<h2>🌱 Inventário de Sementes</h2>
<table>
<tr>
    <th>Nome</th>
    <th>Quantidade</th>
    <th>Qtd Venda</th>
    <th>Preços Totais</th>
    <th>Raridade</th>
    <th>Ações</th>
</tr>
<?php foreach($sementes as $s): ?>
<tr>
    <td><?= htmlspecialchars($s['Nome'] ?? '-') ?></td>
    <td><?= $s['Quantidade'] ?? 0 ?></td>
    <td><input type="number" min="1" max="<?= $s['Quantidade'] ?? 1 ?>" value="1" id="qtdSemente<?= $s['FrutaID'] ?? 0 ?>" style="width:60px"></td>
    <td id="precoSemente<?= $s['FrutaID'] ?? 0 ?>"></td>
    <td><?= $s['Raridade'] ?? '-' ?></td>
    <td>
        <button class="btn" onclick="venderItem('semente', <?= $s['FrutaID'] ?? 0 ?>)">💰 Vender</button>
        <?php if(($s['PrecoTrocaSemente'] ?? 0) > 0): ?>
        <button class="btn" onclick="trocarItem('semente', <?= $s['FrutaID'] ?? 0 ?>)">🔄 Trocar</button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- ==================== INVENTÁRIO DE FRUTAS ==================== -->
<div class="caixa-secao">
<h2>🍎 Inventário de Frutas</h2>
<table>
<tr>
    <th>Nome</th>
    <th>Quantidade</th>
    <th>Qtd Venda</th>
    <th>Preços Totais</th>
    <th>Raridade</th>
    <th>Ações</th>
</tr>
<?php foreach($frutas as $f): ?>
<tr>
    <td><?= htmlspecialchars($f['Nome'] ?? '-') ?></td>
    <td><?= $f['Quantidade'] ?? 0 ?></td>
    <td><input type="number" min="1" max="<?= $f['Quantidade'] ?? 1 ?>" value="1" id="qtdFruta<?= $f['FrutaID'] ?? 0 ?>" style="width:60px"></td>
    <td id="precoFruta<?= $f['FrutaID'] ?? 0 ?>"></td>
    <td><?= $f['Raridade'] ?? '-' ?></td>
    <td>
        <button class="btn" onclick="venderItem('fruta', <?= $f['FrutaID'] ?? 0 ?>)">💰 Vender</button>
        <?php if(($f['PrecoTrocaFruta'] ?? 0) > 0): ?>
        <button class="btn" onclick="trocarItem('fruta', <?= $f['FrutaID'] ?? 0 ?>)">🔄 Trocar</button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- ==================== FAZENDA / SLOTS ==================== -->
<h1>🌱 Sua Fazenda</h1>
<div class="container">
<?php for($i=1;$i<=6;$i++):
    $slot = array_filter($slots, fn($s) => $s['SlotID'] == $i);
    $slot = reset($slot);
?>
<div class="slot" id="slot<?= $i ?>">
<?php if(!$slot || !$slot['FrutaID']): ?>
    <p>Vazio 🌿</p>
    <select id="sem<?= $i ?>">
        <option value="">-- Escolha --</option>
        <?php foreach($sementes as $s): ?>
            <option value="<?= $s['FrutaID'] ?>"><?= htmlspecialchars($s['Nome']) ?> (x<?= $s['Quantidade'] ?>)</option>
        <?php endforeach; ?>
    </select><br>
    <button class="btn" onclick="plantar(<?= $i ?>)">Plantar</button>
<?php else:
    $plantio = $slot['DataPlantio'] instanceof DateTime ? $slot['DataPlantio'] : new DateTime($slot['DataPlantio']);
    $colheita = (clone $plantio)->modify("+{$slot['TempoCrescimento']} minutes");
?>
    <p>🌾 <?= htmlspecialchars($slot['Fruta']) ?></p>
    <p id="tempoRestante<?= $i ?>"></p>
    <button class="btn" id="btnColher<?= $i ?>" onclick="colher(<?= $i ?>)" style="display:none;">Colher</button>
    <script>window["colheita<?= $i ?>"] = <?= $colheita->getTimestamp() ?>;</script>
<?php endif; ?>
</div>
<?php endfor; ?>
</div>

<button class="btn" onclick="colherFrutas()">🌾 Colher Todas as Frutas Maduras</button>

<script>
// ====== Atualiza o tempo restante de cada slot ======
function atualizarTempos() {
    const agora = Math.floor(Date.now() / 1000); // timestamp atual em segundos

    for(let i=1; i<=6; i++) {
        const colheita = window["colheita"+i];
        const elem = document.getElementById("tempoRestante"+i);
        const btn = document.getElementById("btnColher"+i);

        if(!colheita || !elem) continue;

        let restante = colheita - agora;

        if(restante > 0) {
            const horas = Math.floor(restante / 3600);
            const minutos = Math.floor((restante % 3600) / 60);
            const segundos = restante % 60;

            let texto = '';
            if(horas > 0) texto += `${horas}h `;
            if(minutos > 0 || horas > 0) texto += `${minutos}m `;
            texto += `${segundos}s`;

            elem.innerText = `⏳ ${texto}`;
            if(btn) btn.style.display = 'none';
        } else {
            elem.innerText = "✅ Pronto para colher!";
            if(btn) btn.style.display = 'inline-block';
        }
    }
}

// Atualiza a cada 1 segundo
setInterval(atualizarTempos, 1000);
window.onload = atualizarTempos;
</script>

<script>
// Atualiza os preços totais para sementes
<?php foreach($sementes as $s): ?>
const qtdSemente<?= $s['FrutaID'] ?? 0 ?> = document.getElementById('qtdSemente<?= $s['FrutaID'] ?? 0 ?>');
const precoSemente<?= $s['FrutaID'] ?? 0 ?> = document.getElementById('precoSemente<?= $s['FrutaID'] ?? 0 ?>');
qtdSemente<?= $s['FrutaID'] ?? 0 ?>.addEventListener('input', () => {
    let total = qtdSemente<?= $s['FrutaID'] ?? 0 ?>.value * <?= $s['PrecoVendaSemente'] ?? 0 ?>;
    precoSemente<?= $s['FrutaID'] ?? 0 ?>.textContent = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
});
// Inicializa o valor
precoSemente<?= $s['FrutaID'] ?? 0 ?>.textContent = (<?= $s['PrecoVendaSemente'] ?? 0 ?>).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
<?php endforeach; ?>

// Atualiza os preços totais para frutas
<?php foreach($frutas as $f): ?>
const qtdFruta<?= $f['FrutaID'] ?? 0 ?> = document.getElementById('qtdFruta<?= $f['FrutaID'] ?? 0 ?>');
const precoFruta<?= $f['FrutaID'] ?? 0 ?> = document.getElementById('precoFruta<?= $f['FrutaID'] ?? 0 ?>');
qtdFruta<?= $f['FrutaID'] ?? 0 ?>.addEventListener('input', () => {
    let total = qtdFruta<?= $f['FrutaID'] ?? 0 ?>.value * <?= $f['PrecoVendaFruta'] ?? 0 ?>;
    precoFruta<?= $f['FrutaID'] ?? 0 ?>.textContent = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
});
// Inicializa o valor
precoFruta<?= $f['FrutaID'] ?? 0 ?>.textContent = (<?= $f['PrecoVendaFruta'] ?? 0 ?>).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
<?php endforeach; ?>
</script>



<script>
// Função genérica para atualizar preços totais
function atualizarPreco(tipo) {
    // tipo = 'semente' ou 'fruta'
    document.querySelectorAll(`input[id^="qtd${tipo.charAt(0).toUpperCase() + tipo.slice(1)}"]`).forEach(input => {
        const id = input.id.replace(`qtd${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`, '');
        const precoTotalElem = document.getElementById(`${tipo === 'semente' ? 'precoSemente' : 'precoFruta'}${id}`);
        const precoUnit = parseFloat(precoTotalElem.dataset.preco); // vamos guardar o preço unitário no data-preco
        precoTotalElem.textContent = (input.value * precoUnit).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    });
}

// Inicializa os preços e eventos
document.addEventListener('DOMContentLoaded', () => {

    // Sementes
    <?php foreach($sementes as $s): ?>
    const precoSemente<?= $s['FrutaID'] ?? 0 ?> = document.getElementById('precoSemente<?= $s['FrutaID'] ?? 0 ?>');
    precoSemente<?= $s['FrutaID'] ?? 0 ?>.dataset.preco = <?= $s['PrecoVendaSemente'] ?? 0 ?>;
    document.getElementById('qtdSemente<?= $s['FrutaID'] ?? 0 ?>').addEventListener('input', () => atualizarPreco('semente'));
    <?php endforeach; ?>

    // Frutas
    <?php foreach($frutas as $f): ?>
    const precoFruta<?= $f['FrutaID'] ?? 0 ?> = document.getElementById('precoFruta<?= $f['FrutaID'] ?? 0 ?>');
    precoFruta<?= $f['FrutaID'] ?? 0 ?>.dataset.preco = <?= $f['PrecoVendaFruta'] ?? 0 ?>;
    document.getElementById('qtdFruta<?= $f['FrutaID'] ?? 0 ?>').addEventListener('input', () => atualizarPreco('fruta'));
    <?php endforeach; ?>

    // Atualiza todos inicialmente
    atualizarPreco('semente');
    atualizarPreco('fruta');
});
</script>



<!-- ==================== HISTÓRICO ==================== -->
<h2>📜 Últimas Ações na Fazenda</h2>
<table>
<tr><th>Ação</th><th>Fruta</th><th>Quantidade</th><th>Data</th></tr>
<?php foreach($historico as $h): ?>
<tr>
    <td><?= htmlspecialchars($h['Acao'] ?? '-') ?></td>
    <td><?= htmlspecialchars($h['NomeFruta'] ?? '-') ?></td>
    <td><?= $h['Quantidade'] ?? 0 ?></td>
    <td><?= $h['DataRegistro'] instanceof DateTime ? $h['DataRegistro']->format('d/m/Y H:i') : '-' ?></td>
</tr>
<?php endforeach; ?>
</table>

<!-- ==================== SCRIPTS ==================== -->
<script>
// ====== Preços base do PHP ======
const precos = { seemente: {}, fruta: {} };
<?php foreach($todasFrutas as $f): ?>
precos.seemente[<?= $f['FrutaID'] ?>] = {
    venda: <?= $f['PrecoVendaSemente'] ?? 0 ?>,
    compra: <?= $f['PrecoCompraSemente'] ?? ($f['PrecoSemente'] ?? 0) ?>,
    troca: <?= $f['PrecoTrocaSemente'] ?? 0 ?>
};
precos.fruta[<?= $f['FrutaID'] ?>] = {
    venda: <?= $f['PrecoVendaFruta'] ?? 0 ?>,
    compra: <?= $f['PrecoCompraFruta'] ?? ($f['PrecoFruta'] ?? 0) ?>,
    troca: <?= $f['PrecoTrocaFruta'] ?? 0 ?>
};
<?php endforeach; ?>

// ====== Atualiza preços de forma dinâmica ======
function atualizarPreco(tipo, frutaID){
    const input = document.getElementById(`${tipo=='semente'?'qtdSemente':'qtdFruta'}${frutaID}`);
    const totalElem = document.getElementById(`${tipo=='semente'?'precoSemente':'precoFruta'}${frutaID}`);
    if(!input || !totalElem) return;
    let qtd = parseInt(input.value) || 1;
    let p = precos[tipo][frutaID];
    totalElem.textContent = `💎 Venda: ${p.venda*qtd} | Compra: ${p.compra*qtd} | Troca: ${p.troca>0 ? p.troca*qtd : '-'}`;
}

// ====== Inicializa eventos para sementes ======
<?php foreach($sementes as $s): ?>
let s<?= $s['FrutaID'] ?> = document.getElementById('qtdSemente<?= $s['FrutaID'] ?>');
if(s<?= $s['FrutaID'] ?>) s<?= $s['FrutaID'] ?>.addEventListener('input', ()=>atualizarPreco('semente', <?= $s['FrutaID'] ?>));
atualizarPreco('semente', <?= $s['FrutaID'] ?>);
<?php endforeach; ?>

// ====== Inicializa eventos para frutas ======
<?php foreach($frutas as $f): ?>
let f<?= $f['FrutaID'] ?> = document.getElementById('qtdFruta<?= $f['FrutaID'] ?>');
if(f<?= $f['FrutaID'] ?>) f<?= $f['FrutaID'] ?>.addEventListener('input', ()=>atualizarPreco('fruta', <?= $f['FrutaID'] ?>));
atualizarPreco('fruta', <?= $f['FrutaID'] ?>);
<?php endforeach; ?>

// ====== Funções principais ======
function venderItem(tipo, frutaID){
    const qtd = parseInt(document.getElementById(`${tipo=='semente'?'qtdSemente':'qtdFruta'}${frutaID}`).value);
    if(!qtd || qtd<=0){ alert("Quantidade inválida!"); return; }
    fetch('vender.php',{method:'POST',body:new URLSearchParams({tipo,frutaID,quantidade:qtd})})
    .then(r=>r.json()).then(d=>{alert(d.message); if(d.success) location.reload();});
}
function trocarItem(tipo, frutaID){
    const qtd = parseInt(document.getElementById(`${tipo=='semente'?'qtdSemente':'qtdFruta'}${frutaID}`).value);
    if(!qtd || qtd<=0){ alert("Quantidade inválida!"); return; }
    fetch('trocar.php',{method:'POST',body:new URLSearchParams({tipo,frutaID,quantidade:qtd})})
    .then(r=>r.json()).then(d=>{alert(d.message); if(d.success) location.reload();});
}
function comprarSemente(){
    const frutaID=document.getElementById('compraSemente').value;
    if(!frutaID){alert("Escolha uma semente!");return;}
    fetch('comprar_semente.php',{method:'POST',body:new URLSearchParams({frutaID})})
    .then(r=>r.json()).then(d=>{alert(d.message);if(d.success)location.reload();});
}
function plantar(id){
    const frutaID=document.getElementById('sem'+id).value;
    if(!frutaID){alert("Escolha uma semente!");return;}
    fetch('plantar.php',{method:'POST',body:new URLSearchParams({slotID:id,frutaID})})
    .then(r=>r.json()).then(d=>{alert(d.message);if(d.success)location.reload();});
}
function colher(id){
    fetch('colher.php',{method:'POST',body:new URLSearchParams({slotID:id})})
    .then(r=>r.json()).then(d=>{alert(d.message);if(d.success)location.reload();});
}

function colherFrutas() {
    const slotsParaColher = [];

    // Verifica todos os slots e adiciona os que estão prontos
    for (let i = 1; i <= 6; i++) {
        const colheita = window["colheita" + i];
        const agora = Math.floor(Date.now() / 1000);
        if (colheita && colheita - agora <= 0) {
            slotsParaColher.push(i);
        }
    }

    if (slotsParaColher.length === 0) {
        alert("Nenhuma fruta pronta para colher!");
        return;
    }

    // Envia requisição para colher todas
    fetch('colherFrutas.php', {
        method: 'POST',
        body: new URLSearchParams({ slots: slotsParaColher.join(',') }) // envia os slots separados por vírgula
    })
    .then(r => r.json())
    .then(d => {
        alert(d.message);
        if (d.success) location.reload();
    });
}



// ====== Atualização dos tempos ======
function atualizarTempo(){
    const agora = Math.floor(Date.now()/1000);
    for(let i=1;i<=6;i++){
        const elem=document.getElementById("tempoRestante"+i);
        const btn=document.getElementById("btnColher"+i);
        if(!elem || !window["colheita"+i]) continue;
        let restante=window["colheita"+i]-agora;
        if(restante<=0){elem.textContent="✅ Pronto para colher!"; if(btn) btn.style.display="inline-block"; }
        else{
            let h=Math.floor(restante/3600), m=Math.floor((restante%3600)/60), s=restante%60;
            elem.textContent=`⏳ ${h>0?h+"h ":""}${m>0||h>0?m+"m ":""}${s}s`; if(btn) btn.style.display="none";
        }
    }
}
setInterval(atualizarTempo,1000);
window.onload=atualizarTempo;
</script>

</body>
</html>
