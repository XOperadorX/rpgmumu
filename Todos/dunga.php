<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];
$mensagem = "";

// Seleção de personagem
if(isset($_POST['charID'])){
    $charID = intval($_POST['charID']);
    $sql = "SELECT * FROM Characters WHERE CharID = ? AND PlayerID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$charID, $playerID]);
    $char = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if(!$char){
        die("Personagem não encontrado!");
    }

    // Dungeon: Boss
    $bossHP = 100 + ($char['Level'] * 20); // HP do boss aumenta com level
    $playerDamage = rand(10, 30);
    $bossDamage = rand(5, 25);

    // Luta simples: jogador ataca primeiro
    $bossHP -= $playerDamage;

    if($bossHP <= 0){
        $mensagem .= "<p>💀 Boss derrotado! {$char['Name']} ganhou loot especial!</p>";
        $xpGanhos = rand(20, 50) * $char['Level'];
        $ItemsPossiveis = ["Espada de Bronze","Cajado Mágico","Arco Longo","Poção de Vida"];
        $itemDropado = $ItemsPossiveis[array_rand($ItemsPossiveis)];

        // Atualiza XP
        $novaExp = $char['Exp'] + $xpGanhos;
        $novaLevel = $char['Level'];
        // Level Up a cada 100 XP
        if($novaExp >= 100){
            $novaLevel += floor($novaExp/100);
            $novaExp = $novaExp % 100;
            $mensagem .= "<p>✨ {$char['Name']} subiu para o Level {$novaLevel}!</p>";
        }

        sqlsrv_query($conn, "UPDATE Characters SET Exp = ?, Level = ? WHERE CharID = ?", [$novaExp, $novaLevel, $char['CharID']]);
        sqlsrv_query($conn, "INSERT INTO Items (CharID, Name) VALUES (?, ?)", [$char['CharID'], $itemDropado]);

        $mensagem .= "<p>🏆 Ganhou {$xpGanhos} XP e encontrou: <strong>{$itemDropado}</strong></p>";
    } else {
        // Boss contra-ataca
        $charHP = $char['HP'] - $bossDamage;
        if($charHP <= 0){
            $charHP = 0;
            $mensagem .= "<p>💀 {$char['Name']} foi derrotado pelo Boss!</p>";
        } else {
            $mensagem .= "<p>⚔️ {$char['Name']} atacou o boss e causou {$playerDamage} de dano.</p>";
            $mensagem .= "<p>💀 Boss contra-atacou e causou {$bossDamage} de dano.</p>";
        }
        sqlsrv_query($conn, "UPDATE Characters SET HP = ? WHERE CharID = ?", [$charHP, $char['CharID']]);
    }

} else {
    // Seleção do personagem
    $stmt = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID = ?");
    $chars = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $chars[] = $row;
    }

    if(empty($chars)){
        die("<p>Você precisa ter pelo menos um personagem para entrar na dungeon.</p><a href='dashboard.php'>⬅️ Voltar</a>");
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dungeon - Mumu</title>
    <style>
        body { background:#222; color:#f1f1f1; font-family:Arial; text-align:center; padding:30px; }
        a, button { display:inline-block; margin:10px; padding:10px 20px; border:none; background:#444; color:#fff; border-radius:5px; text-decoration:none; cursor:pointer; transition:0.3s; }
        a:hover, button:hover { background:#ffcc00; color:#000; }
        p { background:#333; padding:10px; border-radius:6px; display:inline-block; }
        select, input { margin:5px; padding:8px; border-radius:4px; border:none; }
    </style>
</head>
<body>
    <h1>🗡️ Dungeon Avançada</h1>

    <?php
    if(isset($char)){
        echo $mensagem;
        echo "<br><a href='dung.php'>🏹 Entrar novamente</a>";
    } else {
        echo "<p>Escolha um personagem para entrar na dungeon:</p>";
        echo "<form method='post'>";
        echo "<select name='charID' required>";
        echo "<option value=''>Selecione</option>";
        foreach($chars as $c){
            echo "<option value='{$c['CharID']}'>{$c['Name']} | Level: {$c['Level']} | HP: {$c['HP']}</option>";
        }
        echo "</select><br>";
        echo "<button type='submit'>Entrar na Dungeon</button>";
        echo "</form>";
    }
    ?>
    <br><a href="dashboard.php">⬅️ Voltar para o Painel</a>
</body>
</html>
