<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])){
    header("Location: login.php");
    exit;
}

$playerID = $_SESSION['PlayerID'];

// Consulta usando aliases
$sql = "
SELECT Username,
       Life AS HP,
       MaxLife AS MaxHP,
       Money AS MoedaMumu,
       Bank AS Poupanca,
       Attack AS ATK,
       Defense AS DEF
FROM dbo.Players
WHERE PlayerID=?
";
$stmt = sqlsrv_query($conn, $sql, array($playerID));
if($stmt === false) die(print_r(sqlsrv_errors(), true));
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Inventário
$invSQL = "SELECT ItemID, Nome, Tipo, Icone FROM dbo.Inventory WHERE PlayerID=?";
$invStmt = sqlsrv_query($conn, $invSQL, array($playerID));
if($invStmt === false) die(print_r(sqlsrv_errors(), true));

// Equipamentos
$eqSQL = "SELECT Slot, ItemID FROM dbo.Equipment WHERE PlayerID=?";
$eqStmt = sqlsrv_query($conn, $eqSQL, array($playerID));
if($eqStmt === false) die(print_r(sqlsrv_errors(), true));
$equipamentos = [];
while($eq = sqlsrv_fetch_array($eqStmt, SQLSRV_FETCH_ASSOC)){
    $equipamentos[$eq['Slot']] = $eq['ItemID'];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard RPG Visual</title>
<style>
/* CSS idêntico à versão visual anterior */
</style>
</head>
<body>
<div class="container">
<h1>Bem-vindo, <?=htmlspecialchars($player['Username'])?></h1>

<!-- Moedas e Banco -->
<div class="section">
<h2>💰 Moedas & Banco</h2>
<p>Corrente: <span id="moedas"><?=$player['MoedaMumu']?></span></p>
<p>Poupança: <span id="poupanca"><?=$player['Poupanca']?></span></p>
<input type="number" id="valorBanco" min="1">
<button onclick="depositar()">Depositar</button>
</div>

<!-- Saúde -->
<div class="section">
<h2>❤️ Saúde</h2>
<div class="bar">
    <div id="hpBar" class="bar-inner" style="width:<?=($player['HP']/$player['MaxHP'])*100?>%">
        <?=$player['HP']?>/<?=$player['MaxHP']?>
    </div>
</div>
<br>
<button onclick="comprarPocao()">Comprar Poção (+100HP)</button>
</div>

<!-- Equipamentos -->
<div class="section">
<h2>🛡️ Equipamentos</h2>
<div id="equipSlots">
<?php
$slots = ['Cabeça','Armadura','Arma','Calçado'];
foreach($slots as $slot){
    $itemID = $equipamentos[$slot] ?? '';
    echo "<div class='slot' id='slot-$slot'>$slot";
    if($itemID) echo "<br>Item $itemID";
    echo "</div>";
}
?>
</div>
</div>

<!-- Inventário -->
<div class="section">
<h2>🎒 Inventário</h2>
<div class="grid" id="inventario">
<?php while($item = sqlsrv_fetch_array($invStmt, SQLSRV_FETCH_ASSOC)){
    $icone = $item['Icone'] ?? 'default.png';
    echo "<div class='item-card' onclick='equiparItem({$item['ItemID']})'>
        <img src='icons/$icone' title='{$item['Nome']}'>
    </div>";
} ?>
</div>
</div>

<!-- Dungeon -->
<div class="section">
<h2>🏰 Dungeon</h2>
<div style="position:relative; width:100px; height:100px; background:#eee; border-radius:10px;" id="inimigoArea">
    <span id="statusDungeon" style="position:absolute; top:35%; left:10px;">Goblin</span>
</div>
<button onclick="atacar('Goblin')">Atacar</button>
<div id="logsDungeon"></div>
</div>

</div> <!-- container -->

<script>
// JS idêntico à versão visual anterior (AJAX para moedas, saúde, banco, equip, dungeon)
</script>
</body>
</html>
