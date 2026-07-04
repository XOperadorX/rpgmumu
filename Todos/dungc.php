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

    // --- Definir inimigos aleatórios ---
    $numInimigos = rand(2, 5);
    $inimigos = [];
    for($i=0;$i<$numInimigos;$i++){
        $inimigos[] = [
            "Name"=>"Monstro ".($i+1),
            "HP"=>rand(20, 50),
            "Damage"=>rand(5,15),
            "XP"=>rand(5,20),
            "Loot"=>["Poção de Vida","Armadura Simples","Espada de Bronze"][array_rand(["Poção de Vida","Armadura Simples","Espada de Bronze"])]
        ];
    }

    // Boss final
    $boss = [
        "Name"=>"Boss Final",
        "HP"=>150 + ($char['Level']*30),
        "Damage"=>rand(10,25),
        "XP"=>50 + ($char['Level']*10),
        "Loot"=>["Espada Lendária","Cajado Mágico","Arco Mestre"][array_rand(["Espada Lendária","Cajado Mágico","Arco Mestre"])]
    ];

    $mensagem .= "<p>🏹 {$char['Name']} entrou na dungeon!</p>";

    // --- Combate contra inimigos ---
    foreach($inimigos as $inimigo){
        $mensagem .= "<p>⚔️ Batalha contra {$inimigo['Name']} (HP: {$inimigo['HP']})</p>";
        while($inimigo['HP']>0 && $char['HP']>0){
            $danoJogador = rand(10, 30);
            $inimigo['HP'] -= $danoJogador;
            $mensagem .= "<p>{$char['Name']} causou {$danoJogador} de dano a {$inimigo['Name']} (restante: {$inimigo['HP']})</p>";
            if($inimigo['HP']<=0){
                $mensagem .= "<p>✅ {$inimigo['Name']} derrotado! Ganhou {$inimigo['XP']} XP e item: {$inimigo['Loot']}</p>";
                // Atualiza XP e insere item
                $char['Exp'] += $inimigo['XP'];
                sqlsrv_query($conn,"INSERT INTO Items (CharID, Name) VALUES (?, ?)", [$char['CharID'],$inimigo['Loot']]);
                break;
            }
            // Inimigo ataca
            $char['HP'] -= $inimigo['Damage'];
            $mensagem .= "<p>{$inimigo['Name']} atacou {$char['Name']} e causou {$inimigo['Damage']} de dano (HP restante: {$char['HP']})</p>";
            if($char['HP']<=0){
                $char['HP']=0;
                $mensagem .= "<p>💀 {$char['Name']} foi derrotado na dungeon!</p>";
                break 2; // sai da dungeon
            }
        }
    }

    // --- Combate contra boss ---
    if($char['HP']>0){
        $mensagem .= "<p>💀 Enfrentando o Boss Final!</p>";
        while($boss['HP']>0 && $char['HP']>0){
            $danoJogador = rand(15, 35);
            $boss['HP'] -= $danoJogador;
            $mensagem .= "<p>{$char['Name']} causou {$danoJogador} de dano ao Boss (restante: {$boss['HP']})</p>";
            if($boss['HP']<=0){
                $mensagem .= "<p>🏆 Boss Final derrotado! Ganhou {$boss['XP']} XP e item: {$boss['Loot']}</p>";
                $char['Exp'] += $boss['XP'];
                sqlsrv_query($conn,"INSERT INTO Items (CharID, Name) VALUES (?, ?)", [$char['CharID'],$boss['Loot']]);
                break;
            }
            $char['HP'] -= $boss['Damage'];
            $mensagem .= "<p>Boss Final atacou {$char['Name']} e causou {$boss['Damage']} de dano (HP restante: {$char['HP']})</p>";
            if($char['HP']<=0){
                $char['HP']=0;
                $mensagem .= "<p>💀 {$char['Name']} foi derrotado pelo Boss!</p>";
                break;
            }
        }
    }

    // --- Level up automático ---
    $novaLevel = $char['Level'];
    while($char['Exp']>=100){
        $novaLevel++;
        $char['Exp'] -= 100;
        $mensagem .= "<p>✨ {$char['Name']} subiu para o Level {$novaLevel}!</p>";
    }

    // Atualiza XP, Level e HP no banco
    sqlsrv_query($conn,"UPDATE Characters SET Exp=?, Level=?, HP=? WHERE CharID=?", [$char['Exp'],$novaLevel,$char['HP'],$char['CharID']]);

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
    <title>Dungeon Contínua - Mumu</title>
    <style>
        body { background:#222; color:#f1f1f1; font-family:Arial; text-align:center; padding:30px; }
        a, button { display:inline-block; margin:10px; padding:10px 20px; border:none; background:#444; color:#fff; border-radius:5px; text-decoration:none; cursor:pointer; transition:0.3s; }
        a:hover, button:hover { background:#ffcc00; color:#000; }
        p { background:#333; padding:10px; border-radius:6px; display:inline-block; margin:5px 0; }
        select, input { margin:5px; padding:8px; border-radius:4px; border:none; }
    </style>
</head>
<body>
    <h1>🗡️ Dungeon Contínua</h1>

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
