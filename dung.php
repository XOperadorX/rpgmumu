<?php
// ==========================
// dung.php - Dungeon segura final
// ==========================

// Inicia sessão PHP
session_start();

// Inclui arquivo de conexão com banco de dados
include "db.php";

// Inclui verificação de banimentos
include "check_ban.php";

// Define charset UTF-8 para exibição correta de caracteres
header('Content-Type: text/html; charset=utf-8');

// Verifica se o jogador está logado
if(!isset($_SESSION['PlayerID'])){
    die("⛔ Acesso negado. Faça login."); // Interrompe execução se não estiver logado
}
$playerID = $_SESSION['PlayerID']; // ID do jogador logado

// ==========================
// Inicializa dungeon da sessão se necessário
// ==========================

// Se a dungeon ainda não existir na sessão ou for de outro jogador
if(!isset($_SESSION['dungeon']) || ($_SESSION['dungeon']['playerID'] ?? 0) != $playerID){
    
    $enemies = []; // Array de inimigos da dungeon

    // Seleciona 3 inimigos aleatórios do banco
    $enemyStmt = sqlsrv_query($conn, "SELECT TOP 3 * FROM Enemies ORDER BY NEWID()");
    while($row = sqlsrv_fetch_array($enemyStmt, SQLSRV_FETCH_ASSOC)){
        // Inicializa campos essenciais do inimigo caso não existam
        $row['Name'] = $row['Name'] ?? 'Inimigo';
        $row['Level'] = isset($row['Level']) ? intval($row['Level']) : 1;
        $row['XP'] = isset($row['XP']) ? intval($row['XP']) : 0;
        $row['MaxHP'] = isset($row['MaxHP']) ? intval($row['MaxHP']) : 50;
        $row['HP'] = isset($row['HP']) ? intval($row['HP']) : $row['MaxHP'];
        $row['MaxMana'] = isset($row['MaxMana']) ? intval($row['MaxMana']) : 50;
        $row['Mana'] = isset($row['Mana']) ? intval($row['Mana']) : $row['MaxMana'];
        // Converte loot string em array, remove espaços extras
        $row['Loot'] = !empty($row['Loot']) ? array_map('trim', explode(',', $row['Loot'])) : [];
        $enemies[] = $row;
    }

    // Inicializa dungeon na sessão
    $_SESSION['dungeon'] = [
        'playerID'=>$playerID, // ID do jogador
        'current'=>0,           // Índice do inimigo atual
        'enemies'=>$enemies,    // Lista de inimigos
        'fim'=>false,           // Flag de fim de dungeon
        'char'=>[],             // Personagem será atualizado depois
        'log'=>[]               // Log de ações
    ];
}

// ==========================
// Pega personagem atualizado do banco
// ==========================
$stmt = sqlsrv_query($conn, "SELECT TOP 1 * FROM Characters WHERE PlayerID=?", [$playerID]);
$charDB = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$charDB) die("Você precisa ter pelo menos um personagem. <a href='dashboard.php'>⬅ Voltar</a>");

// ==========================
// Função para inicializar valores do personagem
// ==========================
function initChar(&$char){
    $char['Name'] = $char['Name'] ?? 'Herói';
    $char['Level'] = isset($char['Level']) ? intval($char['Level']) : 1;
    $char['Exp'] = isset($char['Exp']) ? intval($char['Exp']) : 0;
    $char['NextLevelExp'] = isset($char['NextLevelExp']) ? intval($char['NextLevelExp']) : 100;
    $char['MaxHP'] = isset($char['MaxHP']) ? intval($char['MaxHP']) : 100;
    $char['HP'] = isset($char['HP']) ? intval($char['HP']) : $char['MaxHP'];
    $char['MaxMana'] = isset($char['MaxMana']) ? intval($char['MaxMana']) : 100;
    $char['Mana'] = isset($char['Mana']) ? intval($char['Mana']) : $char['MaxMana'];
    $char['MaxPower'] = isset($char['MaxPower']) ? intval($char['MaxPower']) : 100;
    $char['Power'] = isset($char['Power']) ? intval($char['Power']) : $char['MaxPower'];
}

// Inicializa personagem
initChar($charDB);

// ==========================
// Atualiza char da sessão
// ==========================
$dungeon = &$_SESSION['dungeon']; // Referência para sessão
$char = &$dungeon['char'];         // Referência para personagem na dungeon
$char = $charDB;                   // Atualiza valores do banco
initChar($char);                    // Garante inicialização completa

// ==========================
// Variáveis do inimigo atual e controle
// ==========================
$currentEnemy = $dungeon['enemies'][$dungeon['current']] ?? null; // Inimigo atual
$log = &$dungeon['log']; // Referência para log
$hpZerado = $char['HP'] <= 0; // Flag se jogador está morto
$fim = $dungeon['fim'] ?? false; // Flag de fim de dungeon

// Inventário seguro
$inventario = $_SESSION['inventario'] ?? [];
if(!is_array($inventario)) $inventario = []; // Garante array

// ==========================
// Drop do inimigo e envio para mochila
// ==========================
if($currentEnemy && ($currentEnemy['HP'] ?? 0) <= 0 && empty($currentEnemy['Dropped'] ?? null)) {
    
    // Marca inimigo como dropado para não duplicar
    $_SESSION['dungeon']['enemies'][$dungeon['current']]['Dropped'] = true;

    // Pega os itens possíveis do inimigo
    $lootItems = $currentEnemy['Loot'] ?? [];

    foreach($lootItems as $itemID) {
        // Busca informações do item no banco
        $itemStmt = sqlsrv_query($conn, "SELECT * FROM [MumuDB].[dbo].[Items] WHERE ItemID = ?", [$itemID]);
        $item = sqlsrv_fetch_array($itemStmt, SQLSRV_FETCH_ASSOC);
        if(!$item) continue; // Ignora se não encontrar o item

        // Insere item na mochila do jogador
        $insert = "
        INSERT INTO [MumuDB].[dbo].[Mochila]
        (CharID, PlayerID, ItemID, Quantidade, DataAdicionado, PodeUsar, PodeMarcarLixo, PodeEnviarArmazem, PodeSoltar, Posicao)
        VALUES (?, ?, ?, ?, GETDATE(), ?, ?, ?, ?, ?)
        ";
        $params = [
            $char['CharID'] ?? 1,
            $playerID,
            $item['ItemID'],
            $item['Quantidade'] ?? 1,
            $item['PodeUsar'] ?? 1,
            $item['PodeMarcarLixo'] ?? 1,
            $item['PodeEnviarArmazem'] ?? 1,
            $item['PodeSoltar'] ?? 1,
            $item['Posicao'] ?? null
        ];
        sqlsrv_query($conn, $insert, $params); // Executa insert

        // Atualiza inventário da sessão para exibição
        $inventario[$item['ItemID']] = [
            'nome' => $item['Nome'],
            'qtd' => ($inventario[$item['ItemID']]['qtd'] ?? 0) + ($item['Quantidade'] ?? 1),
            'valor' => $item['Valor'] ?? 0
        ];

        // Log de drop
        $log[] = [
            'msg' => "✅ Você recebeu: {$item['Nome']} x".($item['Quantidade'] ?? 1),
            'color' => 'system'
        ];
    }

    // Atualiza inventário na sessão
    $_SESSION['inventario'] = $inventario;
}

// ==========================
// Função para exibir logs em HTML
// ==========================
function logHtml($msg) {
    $text = '';
    $color = 'info';

    if (is_array($msg)) {
        $text = $msg['msg'] ?? '';
        $color = $msg['color'] ?? 'info';
    } else {
        $text = $msg;
    }

    // Mapeamento de cores para classes CSS
    $map = [
        'damage' => 'log-damage',
        'heal'   => 'log-heal',
        'system' => 'log-system',
        'info'   => 'log-info'
    ];
    $class = $map[$color] ?? 'log-info';

    $text = htmlspecialchars($text); // Evita XSS

    return "<div class='log-msg {$class}'>{$text}</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dungeon - RPGMumu</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Orbitron',Arial,sans-serif;background:radial-gradient(circle at top,#0d0d0d 0%,#000 100%);color:#fff;text-align:center;}
.cards-container{display:flex;justify-content:center;flex-wrap:wrap;gap:30px;margin-top:30px;}
.card{background:linear-gradient(145deg,#1a1a1a,#2c2c2c);padding:25px;border-radius:16px;width:300px;position:relative;overflow:hidden;}
.progress{background:#111;border-radius:12px;overflow:hidden;height:22px;margin-bottom:10px;border:1px solid #555;}
.bar{height:100%;border-radius:8px;}
.player-bar{background:linear-gradient(90deg,#ff0055,#ff55aa);}
.mana-bar{background:linear-gradient(90deg,#0066ff,#00ccff);}
.power-bar{background:linear-gradient(90deg,#ffdd33,#ff8800);}
.enemy-bar{background:linear-gradient(90deg,#ff4444,#990000);}
.enemy-mana-bar{background:linear-gradient(90deg,#33ddff,#3399cc);}
.xp-bar{background:linear-gradient(90deg,#33ff33,#00cc00);}
#log{max-height:350px;overflow:auto;text-align:left;margin:25px auto;width:90%;background:#111;padding:15px;border-radius:10px;color:#0ff;}
.log-msg{padding:6px 10px;margin-bottom:6px;border-left:3px solid #0ff;background:rgba(0,255,255,0.05);}
#inventario-container{width:90%;margin:25px auto;text-align:left;background:#111;padding:20px;border-radius:12px;color:#0ff;}
#inventario-container table{width:100%;border-collapse:collapse;}
#inventario-container th, td{padding:12px;border:1px solid #333;text-align:center;}
#voltar{display:none;margin-bottom:20px;color:#ff0;}
</style>

<style>
.log-msg{padding:6px 10px;margin-bottom:6px;border-left:3px solid; background: rgba(0,0,0,0.1);}
.log-info{border-color:#0ff; color:#0ff;}
.log-damage{border-color:#f33; color:#f33;}
.log-heal{border-color:#3f3; color:#3f3;}
.log-system{border-color:#ff0; color:#ff0;}
</style>

</head>
<body>

<a id="voltar" href="dashboard.php">⬅️ Voltar</a>
<h1>🗡️ Masmorra - <?=htmlspecialchars($char['Name'])?></h1>

<?php if($hpZerado): ?>
<p style="color:red;">💀 Você morreu! Dungeon encerrada.</p>
<?php elseif($fim): ?>
<p style="color:yellow;">🏁 Todos os inimigos foram derrotados!</p>
<?php endif; ?>

<div class="cards-container">
    <div class="card">
        <h3><?=htmlspecialchars($char['Name'])?></h3>
        <p>Level: <?=intval($char['Level'])?> | XP: <?=intval($char['Exp'])?> / <?=intval($char['NextLevelExp'])?></p>

        <div class="progress"><div class="bar player-bar" style="width:<?=($char['MaxHP']>0?($char['HP']/$char['MaxHP']*100):0)?>%"></div></div>
        <p>HP: <span id="player-hp"><?=intval($char['HP'])?></span> / <?=intval($char['MaxHP'])?></p>

        <div class="progress"><div class="bar mana-bar" style="width:<?=($char['MaxMana']>0?($char['Mana']/$char['MaxMana']*100):0)?>%"></div></div>
        <p>Mana: <span id="player-mana"><?=intval($char['Mana'])?></span> / <?=intval($char['MaxMana'])?></p>

        <div class="progress"><div class="bar power-bar" style="width:<?=($char['MaxPower']>0?($char['Power']/$char['MaxPower']*100):0)?>%"></div></div>
        <p>Poder: <span id="player-power"><?=intval($char['Power'])?></span> / <?=intval($char['MaxPower'])?></p>

        <div class="progress"><div class="bar xp-bar" style="width:<?=($char['NextLevelExp']>0?($char['Exp']/$char['NextLevelExp']*100):0)?>%"></div></div>
        <p>XP: <span id="player-xp"><?=intval($char['Exp'])?></span> / <?=intval($char['NextLevelExp'])?></p>
    </div>

    <?php if($currentEnemy): ?>
    <div class="card">
        <h3><?=htmlspecialchars($currentEnemy['Name'])?></h3>
        <p>Level: <?=intval($currentEnemy['Level'])?> | XP: <?=intval($currentEnemy['XP'])?></p>
        <div class="progress"><div class="bar enemy-bar" style="width:<?=($currentEnemy['MaxHP']>0?($currentEnemy['HP']/$currentEnemy['MaxHP']*100):0)?>%"></div></div>
        <p>HP: <span id="enemy-hp"><?=intval($currentEnemy['HP'])?></span> / <?=intval($currentEnemy['MaxHP'])?></p>
        <div class="progress"><div class="bar enemy-mana-bar" style="width:<?=($currentEnemy['MaxMana']>0?($currentEnemy['Mana']/$currentEnemy['MaxMana']*100):0)?>%"></div></div>
        <p>Mana: <span id="enemy-mana"><?=intval($currentEnemy['Mana'])?></span> / <?=intval($currentEnemy['MaxMana'])?></p>
    </div>
    <?php endif; ?>
</div>

<h3>📜 Histórico de Combate</h3>
<div id="log">
<?php
if(empty($log)) {
    echo "<div class='log-msg log-system'><em>Nenhum evento registrado ainda.</em></div>";
} else {
    foreach($log as $msg) {
        echo logHtml($msg);
    }
}
?>
</div>




<div id="inventario-container">
<h3>📦 Inventário</h3>
<table id="inv-table">
<tr><th>Item</th><th>Quantidade</th><th>Valor</th></tr>
<?php foreach($inventario as $itemID=>$d): ?>
<tr>
<td><?=htmlspecialchars($d['nome']??$itemID)?></td>
<td id="qtd-<?=$itemID?>"><?=intval($d['qtd']??0)?></td>
<td>💰 <?=intval($d['valor']??0)?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<script>
function safePct(v,m){return m>0?Math.max(0,Math.min(1,v/m)):0;}
function atualizarBarras(c,e){
    document.querySelector('.player-bar').style.width = (safePct(c.HP,c.MaxHP)*100)+'%';
    document.querySelector('.mana-bar').style.width = (safePct(c.Mana,c.MaxMana)*100)+'%';
    document.querySelector('.power-bar').style.width = (safePct(c.Power,c.MaxPower)*100)+'%';
    document.getElementById('player-hp').textContent=c.HP;
    document.getElementById('player-mana').textContent=c.Mana;
    document.getElementById('player-power').textContent=c.Power;
    document.querySelector('.xp-bar').style.width=(safePct(c.Exp,c.NextLevelExp)*100)+'%';
    document.getElementById('player-xp').textContent=c.Exp;

    if(e){
        document.querySelector('.enemy-bar').style.width=(safePct(e.HP,e.MaxHP)*100)+'%';
        document.querySelector('.enemy-mana-bar').style.width=(safePct(e.Mana,e.MaxMana)*100)+'%';
        document.getElementById('enemy-hp').textContent=e.HP;
        document.getElementById('enemy-mana').textContent=e.Mana;
    }
}

function atualizarInventario(inv){
    const table=document.getElementById('inv-table');
    table.innerHTML='<tr><th>Item</th><th>Quantidade</th><th>Valor</th></tr>';
    for(const id in inv){
        const d=inv[id];
        const tr=document.createElement('tr');
        tr.innerHTML=`<td>${d.nome??id}</td><td id="qtd-${id}">${d.qtd??0}</td><td>💰 ${d.valor??0}</td>`;
        table.appendChild(tr);
    }
}

function atualizarLog(logs){
    const el=document.getElementById('log');
    logs.forEach(msg=>{
        const div=document.createElement('div');
        div.className='log-msg';
        div.innerHTML=msg.msg ?? msg;
        el.appendChild(div);
    });
    el.scrollTop=el.scrollHeight;
}

function atualizarDungeon(){
    fetch('turno.php').then(r=>r.json()).then(data=>{
        if(!data) return;
        if(data.char) atualizarBarras(data.char,data.enemy);
        if(data.inventario) atualizarInventario(data.inventario);
        if(data.log) atualizarLog(data.log);
        if(data.fim) document.getElementById('voltar').style.display='inline-block';
        else setTimeout(atualizarDungeon,1200);
    }).catch(err=>console.error('Erro dungeon:',err));
}

<?php if(!$hpZerado && !$fim): ?>
atualizarDungeon();
<?php else: ?>
document.getElementById('voltar').style.display='inline-block';
<?php endif; ?>
</script>
</body>
</html>
