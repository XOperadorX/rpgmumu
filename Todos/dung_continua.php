<?php
session_start();
include "db.php";
if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Selecionar personagens do jogador
$stmt = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID = ?", [$playerID]);
$chars = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $chars[] = $row;
}

if(empty($chars)){
    die("<p>Você precisa ter pelo menos um personagem para entrar na dungeon.</p><a href='dashboard.php'>⬅️ Voltar</a>");
}

// Selecionar primeiro personagem
$char = $chars[0];

// Função salvar log
function salvarLog($conn,$charID,$msg){
    sqlsrv_query($conn, "INSERT INTO DungeonLog (CharID, Message) VALUES (?,?)", [$charID,$msg]);
}

// Função gerar inimigo aleatório
function gerarInimigo(){
    $inimigos = [
        ['Name'=>'Goblin','HP'=>rand(20,35),'XP'=>10,'Loot'=>'Potion'],
        ['Name'=>'Orc','HP'=>rand(40,60),'XP'=>20,'Loot'=>'Gold'],
        ['Name'=>'Troll','HP'=>rand(50,80),'XP'=>30,'Loot'=>'Shield'],
    ];
    $boss = ['Name'=>'Boss Dragão','HP'=>rand(80,120),'XP'=>50,'Loot'=>'Sword'];
    $lista = array_merge($inimigos, [$boss]);
    return $lista[array_rand($lista)];
}

// Combate contínuo: até personagem ou inimigo morrer
$enemy = gerarInimigo();
$enemy['MaxHP'] = $enemy['HP'];
$mensagens = [];

while($enemy['HP']>0 && $char['HP']>0){
    // Jogador ataca
    $danoJogador = rand(5,15);
    $enemy['HP'] -= $danoJogador;
    $enemy['HP'] = max(0,$enemy['HP']);
    $msg = "<span style='color:lightgreen;'>{$char['Name']} atacou {$enemy['Name']} causando {$danoJogador} de dano!</span>";
    $mensagens[] = $msg;
    salvarLog($conn,$char['CharID'],$msg);

    if($enemy['HP']<=0){
        $msg = "<span style='color:yellow;'>{$enemy['Name']} derrotado! {$char['Name']} ganhou {$enemy['XP']} XP e loot: {$enemy['Loot']}</span>";
        $mensagens[] = $msg;
        salvarLog($conn,$char['CharID'],$msg);
        sqlsrv_query($conn, "UPDATE Characters SET Exp=Exp+? WHERE CharID=?", [$enemy['XP'],$char['CharID']]);
        sqlsrv_query($conn, "INSERT INTO Items (CharID, Name) VALUES (?,?)", [$char['CharID'],$enemy['Loot']]);
        break;
    }

    // Inimigo ataca
    $danoInimigo = rand(3,12);
    $char['HP'] -= $danoInimigo;
    $char['HP'] = max(0,$char['HP']);
    $msg = "<span style='color:red;'>{$enemy['Name']} atacou {$char['Name']} causando {$danoInimigo} de dano!</span>";
    $mensagens[] = $msg;
    salvarLog($conn,$char['CharID'],$msg);

    if($char['HP']<=0){
        $msg = "<span style='color:red;'>{$char['Name']} foi derrotado na dungeon!</span>";
        $mensagens[] = $msg;
        salvarLog($conn,$char['CharID'],$msg);
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dungeon Contínua - Mumu</title>
<style>
body { background:#222; color:#f1f1f1; font-family:Arial; text-align:center; padding:30px; }
h1 { color:#ffcc00; }
.progress { width:300px; background:#555; border-radius:5px; margin:5px auto; }
.bar { height:20px; border-radius:5px; transition: width 0.5s; }
.player { background:lightgreen; }
.enemy { background:red; }
.log-container { max-width:700px; margin:20px auto; text-align:left; background:#333; padding:15px; border-radius:8px; height:400px; overflow-y:auto; }
a, button { display:inline-block; margin:10px; padding:10px 20px; border-radius:5px; background:#444; color:#fff; text-decoration:none; transition:0.3s; cursor:pointer; }
a:hover, button:hover { background:#ffcc00; color:#000; }
</style>
</head>
<body>
<h1>🗡️ Dungeon Contínua</h1>

<h2><?= $char['Name'] ?> | HP: <?= $char['HP'] ?></h2>
<div class="progress"><div class="bar player" style="width:<?= ($char['HP']/100)*100 ?>%"></div></div>

<h3><?= $enemy['Name'] ?> | HP: <?= $enemy['HP'] ?></h3>
<div class="progress"><div class="bar enemy" style="width:<?= ($enemy['HP']/$enemy['MaxHP'])*100 ?>%"></div></div>

<div class="log-container" id="logContainer">
    <?php foreach($mensagens as $m): ?>
        <div class="log-message"><?= $m ?></div>
    <?php endforeach; ?>
</div>

<form method="post">
    <button type="submit">🔄 Próximo inimigo</button>
</form>

<a href="dashboard.php">⬅️ Voltar</a>

<script>
var logContainer = document.getElementById('logContainer');
logContainer.scrollTop = logContainer.scrollHeight;
</script>
</body>
</html>
