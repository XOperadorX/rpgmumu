<?php
if (!isset($conn)) include "db.php";
if (!isset($_SESSION)) session_start();

$playerID = $_SESSION['PlayerID'] ?? null;
if(!$playerID) die("⛔ Faça login primeiro.");

// Verifica ban, moedas e recarga
$stmtInfo = sqlsrv_query($conn, "SELECT IsBanned, RecargaExpiraEm, MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
$info = sqlsrv_fetch_array($stmtInfo, SQLSRV_FETCH_ASSOC);
if(!empty($info['IsBanned']) && $info['IsBanned']==1) die("⛔ Você está banido.");

$moedas = $info['MoedaMumu'] ?? 0;
$recargaExpira = $info['RecargaExpiraEm'] ?? null;
$recargaAtiva = false;
$expiraTimestamp = 0;
$expiraFormatada = '';

if($recargaExpira){
    $agora = new DateTime();
    $expira = ($recargaExpira instanceof DateTime)?$recargaExpira:new DateTime($recargaExpira);
    if($agora<$expira){
        $recargaAtiva = true;
        $expiraFormatada = $expira->format('d/m/Y H:i');
        $expiraTimestamp = $expira->getTimestamp();
    }
}

// Busca personagens
$sqlChars = "SELECT TOP 1000 CharID, Name, Class, Level, Exp, HP, MaxHP, Mana, MaxMana, Power 
             FROM Characters WHERE PlayerID=?";
$stmtChars = sqlsrv_query($conn, $sqlChars, [$playerID]);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Personagens</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Orbitron:wght@500&display=swap" rel="stylesheet">
<style>
body{background:#000;color:#fff;font-family:'Roboto',sans-serif;margin:0;padding:20px;}
h1,h2{text-align:center;color:#ffd700;text-shadow:0 0 8px #ffd700;}
.moedas{font-size:1.2em;color:#ff9900;font-weight:bold;text-align:center;text-shadow:0 0 5px #ffcc33;}
.cards{display:flex;flex-wrap:wrap;justify-content:center;gap:20px;margin-top:20px;}
.card{background:#111;border-radius:12px;box-shadow:0 4px 15px rgba(0,255,255,0.2);padding:20px;width:280px;color:#fff;text-align:center;font-family:'Orbitron',sans-serif;transition: transform 0.3s, box-shadow 0.3s;}
.card:hover{transform:translateY(-5px);box-shadow:0 6px 20px rgba(0,255,255,0.4);}
.hp-bar-container,.mana-bar-container,.xp-bar-container,.power-bar-container{background:#222;border-radius:10px;overflow:hidden;height:20px;margin-top:5px;position:relative;}
.hp-bar,.mana-bar,.xp-bar,.power-bar{height:100%;transition:width 0.5s,background 0.5s;}
.hp-bar{background:#4caf50;}.hp-bar.warn{background:#ff9800;}.hp-bar.low{background:#f44336;}
.mana-bar{background:#3498db;}.mana-bar.low{background:#2980b9;}
.xp-bar{background:purple;}.power-bar{background:#ff4500;}.power-bar.low{background:#ff6347;}.power-bar.medium{background:#ff8c00;}.power-bar.high{background:#ffd700;}
.stat-text{position:absolute;width:100%;text-align:center;top:0;left:0;font-weight:bold;color:#fff;font-size:0.9em;line-height:20px;text-shadow:0 0 3px #00ffea;}
.btn{display:inline-block;padding:10px 20px;margin:10px 5px;text-decoration:none;color:#fff;border-radius:8px;background:#222;transition:all 0.3s;font-family:'Orbitron',sans-serif;}
.btn:hover{background:#333;}.btn.delete{background:#e53935;box-shadow:0 0 8px #ff4444;}
.btn-recarga{background:linear-gradient(90deg,#00cc66,#00ff99);color:#fff;font-weight:bold;padding:6px 14px;border-radius:8px;text-decoration:none;box-shadow:0 0 10px #00ff99;display:inline-block;transition:all 0.25s ease;font-size:14px;letter-spacing:0.5px;text-shadow:0 0 3px #008844;border:1px solid rgba(0,255,153,0.4);}
.btn-recarga:hover{background:linear-gradient(90deg,#00e676,#00ffaa);box-shadow:0 0 15px #00ffaa,0 0 5px #00cc66 inset;transform:scale(1.05);}
.btn-recarga:active{transform:scale(0.97);box-shadow:0 0 5px #00cc66;}
.recarga-ativa{background:#00cc66;color:#000;padding:6px 12px;margin:10px auto;border-radius:6px;border:1px solid #00ffea;display:inline-block;font-family:'Orbitron',sans-serif;font-size:13px;text-align:center;font-weight:bold;text-shadow:0 0 5px #00ffea,0 0 10px #00ffea;box-shadow:0 0 10px #00ffea;animation:brilho 2s infinite alternate;}
@keyframes brilho{from{box-shadow:0 0 5px #00ffea;}to{box-shadow:0 0 20px #00ffea;}}
@media(max-width:768px){.card{width:45%;}}
@media(max-width:480px){.card{width:90%;}}
</style>
</head>
<body>

<nav style="text-align:left;">
    <a href="dashboard.php" class="btn">⬅ Voltar</a>
</nav>

<h1>👥 Seus Personagens</h1>
<h2>💰 Moedas Mumu: <span class="moedas"><?= $moedas ?></span></h2>

<div class="recarga-ativa" id="recarga-ativa" style="display:<?= $recargaAtiva?'block':'none' ?>">
    💫 Recarga ativa até <b id="recarga-expira"><?= $expiraFormatada ?></b><br>
    <span class="countdown" id="contador"></span>
</div>

<div style="text-align:center;" id="btn-recarga-container" <?= $recargaAtiva?'style="display:none"':'' ?> >
    <a href="#" id="btn-recarga" class="btn-recarga">⚡ Ativar Recarga Total (1 Dia)</a>
</div>

<div class="cards" id="cards-container">
<?php while($row = sqlsrv_fetch_array($stmtChars, SQLSRV_FETCH_ASSOC)): ?>
<div class="card">
    <h3><?= htmlspecialchars($row['Name']) ?> (<?= htmlspecialchars($row['Class']) ?>)</h3>
    <div>Level: <span id="level-<?= $row['CharID'] ?>"><?= $row['Level'] ?></span></div>

    <div>Poder:</div>
    <div class="power-bar-container">
        <div id="power-<?= $row['CharID'] ?>" class="power-bar" style="width:<?= ($row['Power']??0) ?>%"></div>
        <div class="stat-text" id="powertext-<?= $row['CharID'] ?>"><?= $row['Power']??0 ?> / 100</div>
    </div>

    <div>HP:</div>
    <div class="hp-bar-container">
        <div id="hp-<?= $row['CharID'] ?>" class="hp-bar" style="width:<?= ($row['MaxHP']>0?round($row['HP']/$row['MaxHP']*100):0) ?>%"></div>
        <div class="stat-text" id="hptext-<?= $row['CharID'] ?>"><?= $row['HP'] ?> / <?= $row['MaxHP'] ?></div>
    </div>

    <div>Mana:</div>
    <div class="mana-bar-container">
        <div id="mana-<?= $row['CharID'] ?>" class="mana-bar" style="width:<?= ($row['MaxMana']>0?round($row['Mana']/$row['MaxMana']*100):0) ?>%"></div>
        <div class="stat-text" id="manatext-<?= $row['CharID'] ?>"><?= $row['Mana'] ?> / <?= $row['MaxMana'] ?></div>
    </div>

    <div>XP:</div>
    <div class="xp-bar-container">
        <div id="xp-<?= $row['CharID'] ?>" class="xp-bar" style="width:<?= ($row['Level']>0?min(100,round(($row['Exp']/($row['Level']*50))*100)):0) ?>%"></div>
        <div class="stat-text" id="xptext-<?= $row['CharID'] ?>"><?= $row['Exp'] ?> / <?= $row['Level']*50 ?></div>
    </div>

    <div style="text-align:center;margin-top:20px;">
        <a href="del_personagem.php?CharID=<?= $row['CharID'] ?>" class="btn delete">🗑️ Excluir</a>
    </div>
</div>
<?php endwhile; ?>
</div>
<script>
// =========================
// ======= CONFIGURAÇÃO =====
// =========================
let expiraEm = <?= $expiraTimestamp ?> * 1000; // Timestamp UNIX em ms

// =========================
// ======= CONTADOR ========
// =========================
function formatarTempo(n) {
    return n.toString().padStart(2, '0');
}

function atualizarContador() {
    const agora = Date.now();
    const dist = expiraEm - agora;

    const contadorElem = document.getElementById('contador');
    const recargaAtiva = document.getElementById('recarga-ativa');
    const btnRecargaContainer = document.getElementById('btn-recarga-container');

    if (dist <= 0) {
        if (contadorElem) contadorElem.innerText = "⏳ Recarga expirada!";
        if (recargaAtiva) recargaAtiva.style.display = 'none';
        if (btnRecargaContainer) btnRecargaContainer.style.display = 'inline-block';
        clearInterval(contadorInterval);
        return;
    }

    const dias = Math.floor(dist / (1000 * 60 * 60 * 24));
    const horas = Math.floor((dist % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const mins = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
    const segs = Math.floor((dist % (1000 * 60)) / 1000);

    if (contadorElem) {
        contadorElem.innerText = `⏱️ ${dias}d ${formatarTempo(horas)}h ${formatarTempo(mins)}m ${formatarTempo(segs)}s restantes`;
    }
}

const contadorInterval = setInterval(atualizarContador, 1000);
atualizarContador();

// =========================
// ======= STATUS =========
// =========================
function atualizarBarra(elem, perc, classFunc, textoElem = null, texto = '') {
    if (!elem) return;
    elem.style.width = perc + '%';
    elem.className = classFunc(perc);
    if (textoElem) textoElem.innerText = texto;
}

function atualizarStatus() {
    fetch('status_personagem.php')
        .then(res => res.json())
        .then(data => {
            data.forEach(c => {
                // Cálculo de percentuais
                const hpPerc = c.MaxHP > 0 ? Math.round(c.HP / c.MaxHP * 100) : 0;
                const manaPerc = c.MaxMana > 0 ? Math.round(c.Mana / c.MaxMana * 100) : 0;
                const xpPerc = c.Level > 0 ? Math.round(c.Exp / (c.Level * 50) * 100) : 0;
                const powerPerc = Math.max(0, Math.min(100, c.Power));

                // Funções para classes
                const hpClass = perc => perc > 60 ? 'hp-bar' : (perc > 30 ? 'hp-bar warn' : 'hp-bar low');
                const manaClass = perc => perc > 30 ? 'mana-bar' : 'mana-bar low';
                const powerClass = perc => perc > 70 ? 'power-bar high' : (perc > 30 ? 'power-bar medium' : 'power-bar low');

                // Atualiza elementos
                atualizarBarra(document.getElementById('hp-' + c.CharID), hpPerc, hpClass, document.getElementById('hptext-' + c.CharID), `${c.HP} / ${c.MaxHP}`);
                atualizarBarra(document.getElementById('mana-' + c.CharID), manaPerc, manaClass, document.getElementById('manatext-' + c.CharID), `${c.Mana} / ${c.MaxMana}`);
                atualizarBarra(document.getElementById('xp-' + c.CharID), xpPerc, () => 'xp-bar', document.getElementById('xptext-' + c.CharID), `${c.Exp} / ${c.Level*50}`);
                atualizarBarra(document.getElementById('power-' + c.CharID), powerPerc, powerClass, document.getElementById('powertext-' + c.CharID), `${c.Power} / 100`);

                // Atualiza nível
                const levelElem = document.getElementById('level-' + c.CharID);
                if (levelElem) levelElem.innerText = c.Level;
            });
        })
        .catch(err => console.error('Erro ao atualizar status:', err));
}

// Intervalo de atualização de status
setInterval(atualizarStatus, 1500);

// =========================
// ====== RECARGA =========
// =========================
const btnRecarga = document.getElementById('btn-recarga');
if (btnRecarga) {
    btnRecarga.addEventListener('click', e => {
        e.preventDefault();
        fetch('ativar_recarga.php')
            .then(res => res.json())
            .then(d => {
                if (d.success) {
                    const recargaAtiva = document.getElementById('recarga-ativa');
                    const btnRecargaContainer = document.getElementById('btn-recarga-container');
                    const recargaExpira = document.getElementById('recarga-expira');

                    if (recargaAtiva) recargaAtiva.style.display = 'block';
                    if (btnRecargaContainer) btnRecargaContainer.style.display = 'none';
                    if (recargaExpira) recargaExpira.innerText = new Date(d.timestamp * 1000).toLocaleString();

                    expiraEm = d.timestamp * 1000; // Atualiza contador
                } else {
                    alert(d.message);
                }
            })
            .catch(err => console.error('Erro ao ativar recarga:', err));
    });
}
</script>


</body>
</html>
