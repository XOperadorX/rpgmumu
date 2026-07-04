<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Pega saldo da Moeda Mumu
$moeda = 0;
$stmtMoeda = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
if($stmtMoeda && $row = sqlsrv_fetch_array($stmtMoeda, SQLSRV_FETCH_ASSOC)){
    $moeda = $row['MoedaMumu'];
}

// Pega dados do jogador (login/IP) — usa null se não existir
$player = [];
$stmtPlayer = sqlsrv_query($conn, "SELECT TOP 1 * FROM Players WHERE PlayerID=?", [$playerID]);
if($stmtPlayer && $row = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC)){
    $player = $row;
}

// Pega personagens do jogador
$personagens = [];
$stmtChar = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID=?", [$playerID]);
if($stmtChar && sqlsrv_has_rows($stmtChar)){
    while($row = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC)){
        $personagens[] = $row;
    }
}




?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard Visual - Mumu RPG</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.dashboard-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
.character-card { background: #333; border: 2px solid #555; border-radius: 10px; padding: 15px; width: 220px; text-align: center; transition: 0.3s; }
.character-card:hover { border-color: #ffcc00; box-shadow: 0 0 15px #ffcc00; }
.character-card h3 { color: #ffcc00; margin-bottom: 5px; }
.character-card p { margin: 5px 0; }
.character-card a { display: block; margin: 5px 0; padding: 5px 10px; background: #444; border-radius: 5px; color: #fff; text-decoration: none; transition: 0.3s; }
.character-card a:hover { background: #ffcc00; color: #000; }
.logout-btn { display: inline-block; margin: 20px; padding: 10px 25px; background: #444; color: #fff; border-radius: 8px; text-decoration: none; transition: 0.3s; }
.logout-btn:hover { background: #ff3333; color: #fff; }
.hp-container { background: #555; width: 100%; border-radius: 5px; height: 15px; margin: 5px 0; }
.hp-bar { height: 100%; border-radius: 5px; transition: width 0.5s; background: lightgreen; }
.hp-bar.warn { background: orange; }
.hp-bar.low  { background: red; }
</style>
</head>
<body>


<div style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <a href="logout.php" class="logout-btn">🚪 Sair</a>
    <a href="del_conta.php" class="logout-btn" 
       onclick="return confirm('Tem certeza que deseja excluir sua conta? Esta ação é irreversível!');">
       🗑️ Excluir Conta
    </a>
</div>

<!-- Área do painel -->
<div style="margin-top:80px; text-align:center;">
    <h1>🏰 Painel Visual do Mumu RPG</h1>
    <p>Bem-vindo, aventureiro!</p>
</div>


<div style="background:#333; color:#ffcc00; padding:10px; border-radius:8px; display:inline-block; margin-bottom:20px;">
    <p>💰 Moedas Mumu: <strong><?= $moeda ?></strong></p>
    <p><strong>Último login:</strong> <?= !empty($player['UltimoLogin']) ? $player['UltimoLogin']->format("d/m/Y H:i") : "Nunca" ?></p>
    <p><strong>IP logado:</strong> <?= !empty($player['UltimoIP']) ? htmlspecialchars($player['UltimoIP']) : "Desconhecido" ?></p>
</div>

<div class="dashboard-container">
<?php if(!empty($personagens)): ?>
    <?php foreach($personagens as $char): ?>
    <div class="character-card" data-charid="<?= $char['CharID'] ?>">
        <h3><?= htmlspecialchars($char['Name']) ?> (<?= htmlspecialchars($char['Class']) ?>)</h3>
        <p id="level-<?= $char['CharID'] ?>">Level: <?= $char['Level'] ?></p>
        <p id="xp-<?= $char['CharID'] ?>">XP: <?= $char['Exp'] ?? 0 ?></p>

        <?php
        $hp = $char['Life'] ?? $char['HP'] ?? 100;
        $max = $char['MaxLife'] ?? $char['MaxHP'] ?? 100;
        $percent = max(0, min(100, round($hp / $max * 100)));
        $cls = ($percent > 60) ? '' : (($percent > 30) ? 'warn' : 'low');
        ?>
		
		
        <div class="hp-container" role="progressbar" aria-valuemin="0" aria-valuemax="<?= $max ?>" aria-valuenow="<?= $hp ?>">
            <div id="hp-<?= $char['CharID'] ?>" class="hp-bar <?= $cls ?>" style="width:<?= $percent ?>%"></div>
        </div>
        <div class="hp-text" id="hptext-<?= $char['CharID'] ?>">❤️ HP: <?= $hp ?> / <?= $max ?> (<?= $percent ?>%)</div>

			<a href="personagens.php?CharID=<?= $char['CharID'] ?>">👤 Ver Detalhes</a>
			<a href="bank.php?CharID=<?= $char['CharID'] ?>">🏦 Banco</a>
			<a href="dung.php?CharID=<?= $char['CharID'] ?>">🗡️ Masmorra</a>
			<a href="equipar_item_advanced.php?CharID=<?= $char['CharID'] ?>">🎒 Inventário</a>
			<a href="saude.php?CharID=<?= $char['CharID'] ?>">🩺 Saúde da Conta</a>
			<a href="del_personagem.php?CharID=<?= $char['CharID'] ?>" 
			   onclick="return confirm('Tem certeza que deseja excluir este personagem? Esta ação é irreversível!');">
			   🗑️ Excluir
			</a>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Nenhum personagem encontrado. <a href="criar_personagem.php">Crie um agora!</a></p>
<?php endif; ?>
</div>











<script>
// Atualiza HP/XP/Level de todos os personagens
function atualizarStatus() {
    fetch('status_personagem.php')
    .then(res => res.json())
    .then(data => {
        data.forEach(char => {
            let hpElem = document.getElementById('hp-' + char.CharID);
            let hpText = document.getElementById('hptext-' + char.CharID);
            let percent = Math.max(0, Math.min(100, Math.round(char.HP / char.MaxHP * 100)));
            hpElem.style.width = percent + '%';
            hpElem.className = (percent > 60) ? 'hp-bar' : ((percent > 30) ? 'hp-bar warn' : 'hp-bar low');
            hpText.innerText = '❤️ HP: ' + char.HP + ' / ' + char.MaxHP + ' (' + percent + '%)';
            document.getElementById('level-' + char.CharID).innerText = 'Level: ' + char.Level;
            document.getElementById('xp-' + char.CharID).innerText = 'XP: ' + char.Exp;
        });
    })
    .catch(err => console.error(err));
}
setInterval(atualizarStatus, 1500);
</script>

</body>
</html>
