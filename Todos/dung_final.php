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

// Pega o primeiro personagem como exemplo
$char = $chars[0];

// Inimigos da dungeon
$inimigos = [
    ['Name'=>'Goblin','HP'=>30,'MaxHP'=>30,'XP'=>10,'Loot'=>'Potion'],
    ['Name'=>'Orc','HP'=>50,'MaxHP'=>50,'XP'=>20,'Loot'=>'Gold'],
    ['Name'=>'Boss Dragão','HP'=>100,'MaxHP'=>100,'XP'=>50,'Loot'=>'Sword']
];

// Função salvar log
function salvarLog($conn,$charID,$msg){
    sqlsrv_query($conn, "INSERT INTO DungeonLog (CharID, Message) VALUES (?,?)", [$charID,$msg]);
}

$mensagens = [];

// Combate
foreach($inimigos as &$enemy){
    while($enemy['HP']>0 && $char['HP']>0){
        // Jogador ataca
        $danoJogador = rand(5,15);
        $enemy['HP'] -= $danoJogador;
        $enemy['HP'] = max(0,$enemy['HP']);
        $msg = "<span style='color:lightgreen;'>{$char['Name']} atacou {$enemy['Name']} causando {$danoJogador} de dano!</span>";
        $mensagens[] = $msg;
        salvarLog($conn,$char['CharID'],$msg);

        if($enemy['HP']<=0){
            $msg = "<span style='color:yellow;'>{$enemy['Name']} foi derrotado! {$char['Name']} ganhou {$enemy['XP']} XP e loot: {$enemy['Loot']}</span>";
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
            break 2;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dungeon Final - Mumu</title>
<style>
body { background:#222; color:#f1f1f1; font-family:Arial; text-align:center; padding:30px; }
h1 { color:#ffcc00; }
.progress { width:300px; background:#555; border-radius:5px; margin:5px auto; }
.bar { height:20px; border-radius:5px; transition: width 0.5s; }
.player { background:lightgreen; }
.enemy { background:red; }
.log-container { max-width:700px; margin:20px auto; text-align:left; background:#333; padding:15px; border-radius:8px; height:400px; overflow-y:auto; }
a { display:inline-block; margin:15px; padding:10px 20px; border-radius:5px; background:#444; color:#fff; text-decoration:none; transition:0.3s; }
a:hover { background:#ffcc00; color:#000; }
</style>
</head>
<body>
<h1>🗡️ Dungeon</h1>

<h2><?= $char['Name'] ?> | HP: <?= $char['HP'] ?></h2>
<div class="progress"><div id="playerBar" class="bar player" style="width:<?= ($char['HP']/100)*100 ?>%"></div></div>

<?php foreach($inimigos as $enemy): ?>
<h3><?= $enemy['Name'] ?> | HP: <?= $enemy['HP'] ?></h3>
<div class="progress"><div class="bar enemy" style="width:<?= ($enemy['HP']/$enemy['MaxHP'])*100 ?>%"></div></div>
<?php endforeach; ?>

<div class="log-container" id="logContainer">
    <?php foreach($mensagens as $m): ?>
        <div class="log-message"><?= $m ?></div>
    <?php endforeach; ?>
</div>

<a href="dashboard.php">⬅️ Voltar</a>

<script>
// Scroll automático para o final dos logs
var logContainer = document.getElementById('logContainer');
logContainer.scrollTop = logContainer.scrollHeight;
</script>
</body>
</html>
