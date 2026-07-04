<?php
session_start();
include "db.php";
include "check_ban.php"; // Protege a página

if (!isset($_SESSION['PlayerID'])) {
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Dados do jogador
$stmtPlayer = sqlsrv_query($conn, "SELECT TOP 1 * FROM Players WHERE PlayerID=?", [$playerID]);
$player = ($stmtPlayer && $row = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC)) ? $row : [];
$moedas = $player['MoedaMumu'] ?? 0;

// Personagens
$personagens = [];
$sql = "SELECT TOP 1000 [CharID],[PlayerID],[Name],[Class],[Level],[Exp],[HP],[Mana],[MaxHP],[MaxMana],[Power]
        FROM Characters WHERE PlayerID=?";
$stmtChar = sqlsrv_query($conn, $sql, [$playerID]);
if ($stmtChar && sqlsrv_has_rows($stmtChar)) {
    while ($row = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC)) {
        $personagens[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard - Mumu RPG</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<style>
/* RESET & BODY */
body { background:black; color:white; font-family:'Roboto',sans-serif; margin:0; padding:20px; }
.container { max-width:1200px; margin:auto; padding:20px; }

/* TOP BAR */
.top-bar {
    display:flex; flex-wrap:wrap; justify-content:center;
    gap:10px; margin-bottom:20px;
}
.top-bar a, .top-bar button {
    padding:10px 20px; border-radius:8px; text-decoration:none;
    color:#fff; background:#333; transition:0.3s; border:none; cursor:pointer;
}
.top-bar a:hover, .top-bar button:hover { background:#ffcc00; color:#000; }

/* BLOCO JOGADOR */
.jogador-info {
    background:#222; color:#ffcc00;
    padding:15px; border-radius:10px;
    margin:0 auto 20px auto; text-align:center; max-width:800px;
}
.jogador-info h1{margin-top:0; font-size:1.8em;}
.jogador-info p{margin:5px 0;}

/* PERSONAGENS */
.cards { display:flex; flex-wrap:wrap; justify-content:center; gap:20px; }
.card {
    background:#1a1a1a; border-radius:12px; box-shadow:0 4px 12px rgba(255,255,255,0.1);
    padding:20px; width:260px; transition:transform 0.2s; color:white; text-align:center;
}
.card:hover { transform:translateY(-5px); }
.card h3 { color:#ffd700; margin-top:0; }

/* BARRAS */
.hp-bar-container,.mana-bar-container,.xp-bar-container,.power-bar-container {
    background:#333; border-radius:10px; overflow:hidden; height:20px;
    margin-top:5px; position:relative;
}
.hp-bar,.mana-bar,.xp-bar,.power-bar {
    height:100%; transition:width 0.5s;
}
.hp-bar { background:red; }
.hp-bar.warn { background:red; animation:pulse 1s infinite; }
.hp-bar.low { background:red; animation:pulse 0.8s infinite; }
.mana-bar { background:#3498db; }
.mana-bar.low { background:#2980b9; animation:pulse 0.8s infinite; }
.xp-bar { background:purple; }
.power-bar.low { background:#16a085; animation:pulse 0.8s infinite; }
.power-bar.medium { background:#27ae60; }
.power-bar.high { background:#2ecc71; box-shadow:0 0 8px #2ecc71; animation:glow 1.2s infinite; }

.stat-text {
    position:absolute; width:100%; text-align:center;
    top:0; left:0; font-weight:bold; color:#fff;
    font-size:0.9em; line-height:20px;
}

/* ANIMAÇÕES */
@keyframes pulse {
  0%{box-shadow:0 0 5px rgba(255,255,255,0.5);}
  50%{box-shadow:0 0 15px rgba(255,255,255,1);}
  100%{box-shadow:0 0 5px rgba(255,255,255,0.5);}
}
@keyframes glow {
  0%{box-shadow:0 0 5px gold;}
  50%{box-shadow:0 0 20px gold;}
  100%{box-shadow:0 0 5px gold;}
}

/* RESPONSIVO */
@media(max-width:768px){ .card{width:45%;} }
@media(max-width:480px){ .card{width:90%; .top-bar{flex-direction:column;align-items:center;} } }
</style>
</head>
<body>
<div class="container">

    <!-- MENU -->
    <nav class="top-bar">
        <form method="POST" action="restaurar.php"><button type="submit">💊 Restaurar HP/MP</button></form>
        <a href="logout.php">🚪 Sair</a>
        <a href="personagens.php">👤 Personagens</a>
        <a href="bank.php">🏦 Banco</a>
        <a href="start_dungeon.php">🗡️ Masmorra</a>
        <a href="inventario.php">🎒 Inventário</a>
        <a href="saude.php">🩺 Saúde</a>
        <a href="mercado.php">🛒 Mercado</a>
        <a href="bolsa.php">📈 Bolsa</a>
        <a href="del_conta.php" onclick="return confirm('Tem certeza?');">🗑️ Excluir Conta</a>
    </nav>

    <!-- INFO JOGADOR -->
    <div class="jogador-info">
        <p>🏰 Painel Visual do Mumu RPG</p>
        <p>Bem-vindo, <strong><?= htmlspecialchars($player['Username'] ?? 'Jogador') ?></strong>!</p>
        <p>💰 Moedas Mumu: <strong><?= $moedas ?></strong></p>
        <p><strong>Último login:</strong> <?= !empty($player['LastLoginTime']) ? $player['LastLoginTime']->format("d/m/Y H:i") : "Nunca" ?></p>
        <p><strong>IP logado:</strong> <?= !empty($player['LastLoginIP']) ? htmlspecialchars($player['LastLoginIP']) : "Desconhecido" ?></p>
    </div>

    <!-- PERSONAGENS -->
    <h2 style="text-align:center; color:#ffd700;">👥 Seus Personagens</h2>
    <div class="cards" id="cards-container">
    <?php if ($personagens): foreach ($personagens as $row): ?>
        <div class="card">
            <h3><?= htmlspecialchars($row['Name']) ?> (<?= htmlspecialchars($row['Class']) ?>)</h3>
            <div>Level: <span id="level-<?= $row['CharID'] ?>"><?= $row['Level'] ?></span></div>

            <!-- Poder -->
            <div>Poder:</div>
            <div class="power-bar-container">
                <div id="power-<?= $row['CharID'] ?>" class="power-bar" style="width:<?= $row['Power'] ?>%"></div>
                <div class="stat-text" id="powertext-<?= $row['CharID'] ?>"><?= $row['Power'] ?> / 100</div>
            </div>

            <!-- HP -->
            <div>HP:</div>
            <div class="hp-bar-container">
                <div id="hp-<?= $row['CharID'] ?>" class="hp-bar" style="width:<?= ($row['MaxHP']>0?round($row['HP']/$row['MaxHP']*100):0) ?>%"></div>
                <div class="stat-text" id="hptext-<?= $row['CharID'] ?>"><?= $row['HP'] ?> / <?= $row['MaxHP'] ?></div>
            </div>

            <!-- Mana -->
            <div>Mana:</div>
            <div class="mana-bar-container">
                <div id="mana-<?= $row['CharID'] ?>" class="mana-bar" style="width:<?= ($row['MaxMana']>0?round($row['Mana']/$row['MaxMana']*100):0) ?>%"></div>
                <div class="stat-text" id="manatext-<?= $row['CharID'] ?>"><?= $row['Mana'] ?> / <?= $row['MaxMana'] ?></div>
            </div>

            <!-- XP -->
            <div>XP:</div>
            <div class="xp-bar-container">
                <div id="xp-<?= $row['CharID'] ?>" class="xp-bar" style="width:<?= ($row['Level']>0 ? min(100, round(($row['Exp']/($row['Level']*50))*100)) : 0) ?>%"></div>
                <div class="stat-text" id="xptext-<?= $row['CharID'] ?>"><?= $row['Exp'] ?> / <?= $row['Level']*50 ?></div>
            </div>
        </div>
    <?php endforeach; else: ?>
        <p>Nenhum personagem encontrado. <a href="criar_personagem.php">Crie um agora!</a></p>
    <?php endif; ?>
    </div>
</div>

<script>
// Atualiza status via AJAX
function atualizarStatus(){
 fetch('status_personagem.php')
 .then(r=>r.json())
 .then(data=>{
   data.forEach(c=>{
     let hpPerc=Math.round(c.HP/c.MaxHP*100);
     let manaPerc=Math.round(c.Mana/c.MaxMana*100);
     let xpPerc=Math.round((c.Exp/(c.Level*50))*100);
     let powerPerc=c.Power;

     let hp=document.getElementById('hp-'+c.CharID);
     hp.style.width=hpPerc+'%';
     hp.className=(hpPerc>60?'hp-bar':(hpPerc>30?'hp-bar warn':'hp-bar low'));
     document.getElementById('hptext-'+c.CharID).innerText=c.HP+" / "+c.MaxHP;

     let mana=document.getElementById('mana-'+c.CharID);
     mana.style.width=manaPerc+'%';
     mana.className=(manaPerc>30?'mana-bar':'mana-bar low');
     document.getElementById('manatext-'+c.CharID).innerText=c.Mana+" / "+c.MaxMana;

     let xp=document.getElementById('xp-'+c.CharID);
     xp.style.width=xpPerc+'%';
     document.getElementById('xptext-'+c.CharID).innerText=c.Exp+" / "+(c.Level*50);

     let power=document.getElementById('power-'+c.CharID);
     power.style.width=powerPerc+'%';
     if(powerPerc>70) power.className='power-bar high';
     else if(powerPerc>30) power.className='power-bar medium';
     else power.className='power-bar low';
     document.getElementById('powertext-'+c.CharID).innerText=c.Power+" / 100";
   });
 });
}
setInterval(atualizarStatus,1500);
</script>
</body>
</html>
