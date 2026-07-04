<?php
include "db.php"; // Conexão com o SQL Server

// Puxa todos os inimigos
$sql = "SELECT * FROM dbo.Enemies";
$stmt = sqlsrv_query($conn, $sql);
if($stmt === false){ die(print_r(sqlsrv_errors(), true)); }

$inimigos = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $row['HP'] = intval($row['HP']??0);
    $row['MaxHP'] = intval($row['MaxHP']??$row['HP']);
    $row['Mana'] = intval($row['Mana']??0);
    $row['MaxMana'] = intval($row['MaxMana']??$row['Mana']);
    $row['XP'] = intval($row['XP']??0);
    $row['Attack'] = intval($row['Attack']??0);
    $row['Defense'] = intval($row['Defense']??0);
    $row['MagicAttack'] = intval($row['MagicAttack']??0);
    $row['MagicDefense'] = intval($row['MagicDefense']??0);
    $row['Speed'] = intval($row['Speed']??0);
    $row['CritChance'] = intval($row['CritChance']??0);
    $row['Loot'] = !empty($row['Loot']) ? json_decode($row['Loot'], true) : [];
    $inimigos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Lista de Inimigos</title>
<style>
body{background:#000;color:#fff;font-family:'Orbitron',sans-serif;text-align:center;}
.cards-container{ display:flex; flex-wrap:wrap; justify-content:center; gap:20px; margin-top:30px; }
.card{ background:linear-gradient(145deg,#1a1a1a,#2c2c2c); padding:20px; border-radius:16px; width:250px; box-shadow:0 0 15px #ff4444 inset,0 0 15px #ff0000; border:2px solid #ff4444; }
.card h3{margin:5px 0;}
.progress{background:#111; border-radius:12px; overflow:hidden; height:20px; margin-bottom:5px; border:1px solid #555;}
.bar{height:100%; border-radius:8px; transition:width .3s;}
.bar.hp-bar{background:linear-gradient(90deg,#ff4444,#990000);}
.bar.mana-bar{background:linear-gradient(90deg,#33ddff,#3399cc);}
.card ul{list-style:none; padding-left:0;}
.card ul li{margin-bottom:4px;}
</style>
</head>
<body>
<h1>🧟‍♂️ Lista de Inimigos</h1>
<div class="cards-container">
<?php foreach($inimigos as $e): ?>
<div class="card">
    <h3><?=htmlspecialchars($e['Name'])?></h3>
    <p>Level: <?=$e['Level']?> | XP: <?=$e['XP']?></p>
    <div class="progress"><div class="bar hp-bar" style="width:<?=($e['MaxHP']>0? $e['HP']/$e['MaxHP']*100 : 0)?>%"></div></div>
    <p>HP: <?=$e['HP']?> / <?=$e['MaxHP']?></p>
    <div class="progress"><div class="bar mana-bar" style="width:<?=($e['MaxMana']>0? $e['Mana']/$e['MaxMana']*100 : 0)?>%"></div></div>
    <p>Mana: <?=$e['Mana']?> / <?=$e['MaxMana']?></p>
    <p>Attack: <?=$e['Attack']?> | Defense: <?=$e['Defense']?></p>
    <p>Magic Attack: <?=$e['MagicAttack']?> | Magic Defense: <?=$e['MagicDefense']?></p>
    <p>Speed: <?=$e['Speed']?> | Crit: <?=$e['CritChance']?>%</p>
    <p>Element: <?=htmlspecialchars($e['Element'])?></p>
    <p>Skill: <?=htmlspecialchars($e['SpecialSkill'])?></p>
    <h4>Loot:</h4>
    <ul>
        <?php foreach($e['Loot'] as $i): ?>
            <li><?=htmlspecialchars($i['nome'])?> x<?=$i['qtd']?> (<?=htmlspecialchars($i['raridade']??'comum')?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endforeach; ?>
</div>
</body>
</html>
