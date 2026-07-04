<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];


$sql = "SELECT c.CharID, c.Name, c.Class, c.Level, c.Exp, c.HP, pc.MoedaMumu
        FROM Characters c
        JOIN Players pc ON c.PlayerID = pc.PlayerID
        WHERE c.PlayerID = ?";

$params = array($playerID);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}




?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($char['Name']) ?> - Perfil do Personagem</title>
<style>
body { background:#1c1c1c; color:#fff; font-family: Arial, sans-serif; text-align:center; padding:20px; }
.container { background:#2b2b2b; border-radius:10px; padding:20px; width:400px; margin:auto; }
h1 { color:#ffcc00; }
.stat { margin:10px 0; font-size:16px; }
.hp-container {
    background: #555;
    width: 100%;
    border-radius: 5px;
    height: 20px;
    margin: 10px 0;
}
.hp-bar {
    height: 100%;
    border-radius: 5px;
    transition: width 0.5s;
    background: lightgreen;
}
.hp-bar.warn { background: orange; }
.hp-bar.low  { background: red; }
a.btn { display:inline-block; margin:10px; padding:10px 20px; border-radius:5px; text-decoration:none; font-weight:bold; }
.voltar { background:#444; color:#fff; }
.voltar:hover { background:#666; }
</style>
</head>
<body>
<div class="container">
    <h1>👤 <?= htmlspecialchars($char['Name']) ?></h1>
    <div class="stat"><strong>Classe:</strong> <?= htmlspecialchars($char['Class']) ?></div>
    <div class="stat"><strong>Level:</strong> <?= $char['Level'] ?></div>
    <div class="stat"><strong>Experiência:</strong> <?= $char['Exp'] ?></div>

    <?php
        $hp = $char['Life'];
        $max = $char['MaxLife'];
        $percent = ($max > 0) ? round($hp / $max * 100) : 0;
        $cls = ($percent > 60) ? '' : (($percent > 30) ? 'warn' : 'low');
    ?>
    <div class="stat"><strong>HP:</strong> <?= $hp ?> / <?= $max ?> (<?= $percent ?>%)</div>
    <div class="hp-container">
        <div class="hp-bar <?= $cls ?>" style="width:<?= $percent ?>%"></div>
    </div>

    <a href="dashboard.php" class="btn voltar">⬅️ Voltar ao Painel</a>
</div>
</body>
</html>
