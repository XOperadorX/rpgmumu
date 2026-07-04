<?php
session_start();
include "db.php";
include "check_ban.php";

if(!isset($_SESSION['PlayerID'])) die("Acesso negado.");

$playerID = $_SESSION['PlayerID'];
$dungeon = &$_SESSION['dungeon'];

if(!isset($_SESSION['loot'])) $_SESSION['loot'] = [];

$char = null;
if(isset($_GET['action']) && $_GET['action'] === 'run') {
    // Processa os turnos do dungeon
    $stmt = sqlsrv_query($conn, "SELECT TOP 1 * FROM Characters WHERE PlayerID=?", [$playerID]);
    $char = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    $turns = [];

    while(isset($dungeon['enemies'][$dungeon['current']]) && $char['HP'] > 0){
        $enemy = &$dungeon['enemies'][$dungeon['current']];
        
        // Turno do jogador
        $damage = rand(5,15);
        $enemy['HP'] -= $damage;
        $enemy['HP'] = max(0, $enemy['HP']);
        $turns[] = [
            'type'=>'player_attack',
            'text'=>"Você causou $damage de dano a {$enemy['Name']}.",
            'enemyHP'=>$enemy['HP']
        ];

        sqlsrv_query($conn, "UPDATE Enemies SET HP=? WHERE EnemyID=?", [$enemy['HP'], $enemy['EnemyID']]);

        // Inimigo morto
        if($enemy['HP'] <= 0){
            $turns[] = [
                'type'=>'enemy_dead',
                'text'=>"Inimigo {$enemy['Name']} derrotado!"
            ];

            if(isset($enemy['Loot'])){
                foreach($enemy['Loot'] as $item){
                    if(rand(1,100) <= $item['ChanceFinal']){
                        $_SESSION['loot'][] = ['ItemName'=>$item['ItemName'],'Rarity'=>$item['Rarity']];
                        sqlsrv_query($conn, "INSERT INTO Inventory (PlayerID, ItemName, Rarity) VALUES (?,?,?)",
                            [$playerID, $item['ItemName'], $item['Rarity']]);
                        $turns[] = [
                            'type'=>'loot',
                            'text'=>"Você recebeu o item: {$item['ItemName']} (Raridade: {$item['Rarity']})!"
                        ];
                    }
                }
            }

            $dungeon['current']++;
            if(!isset($dungeon['enemies'][$dungeon['current']])){
                $dungeon['fim'] = true;
                break;
            }
            $enemy = &$dungeon['enemies'][$dungeon['current']];
            continue;
        }

        // Dano do inimigo
        $enemyDamage = rand(3,10);
        $char['HP'] -= $enemyDamage;
        $char['HP'] = max(0, $char['HP']);
        $turns[] = [
            'type'=>'enemy_attack',
            'text'=>"{$enemy['Name']} atacou você causando $enemyDamage de dano.",
            'playerHP'=>$char['HP']
        ];
    }

    // Atualiza stats do jogador
    sqlsrv_query($conn, "UPDATE Characters SET HP=?, Mana=?, Exp=? WHERE PlayerID=?", [$char['HP'], $char['Mana'], $char['Exp'], $playerID]);

    // Retorna JSON
    header('Content-Type: application/json');
    echo json_encode([
        'char'=>[
            'HP'=>$char['HP'],
            'MaxHP'=>$char['MaxHP'],
            'Mana'=>$char['Mana'],
            'MaxMana'=>$char['MaxMana'],
            'Exp'=>$char['Exp'],
            'Level'=>$char['Level']
        ],
        'enemy'=> $dungeon['enemies'][$dungeon['current']] ?? null,
        'fim'=> $dungeon['fim'] ?? false,
        'turns'=> $turns,
        'loot'=> $_SESSION['loot']
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dungeon Automática</title>
<style>
body { background:#111; color:#0f0; font-family: monospace; padding: 20px; }
#log { height:400px; overflow-y:auto; background:#222; padding:10px; margin-bottom:10px; }
button { padding:10px 20px; font-size:16px; cursor:pointer; }
</style>
</head>
<body>

<h2>Status do jogador</h2>
<p id="charHP"></p>
<p id="charMana"></p>
<p id="charLevel"></p>

<h2>Dungeon</h2>
<div id="log"></div>
<button id="start">Iniciar Dungeon</button>

<script>
document.getElementById('start').onclick = () => {
    fetch('?action=run')
    .then(res => res.json())
    .then(data => {
        const logDiv = document.getElementById('log');
        logDiv.innerHTML = '';

        const charHP = document.getElementById('charHP');
        const charMana = document.getElementById('charMana');
        const charLevel = document.getElementById('charLevel');

        // Atualiza status inicial
        charHP.textContent = `HP: ${data.char.HP} / ${data.char.MaxHP}`;
        charMana.textContent = `Mana: ${data.char.Mana} / ${data.char.MaxMana}`;
        charLevel.textContent = `Level: ${data.char.Level}`;

        let turnIndex = 0;

        function showNextTurn() {
            if(turnIndex < data.turns.length){
                const turn = data.turns[turnIndex];

                const p = document.createElement('p');
                p.textContent = turn.text;

                if(turn.type === 'player_attack') p.style.color = '#0f0';
                else if(turn.type === 'enemy_attack') p.style.color = '#f00';
                else if(turn.type === 'enemy_dead') p.style.color = '#ff0';
                else if(turn.type === 'loot') p.style.color = '#0ff';

                logDiv.appendChild(p);
                logDiv.scrollTop = logDiv.scrollHeight;

                if(turn.playerHP !== undefined) charHP.textContent = `HP: ${turn.playerHP} / ${data.char.MaxHP}`;

                turnIndex++;
                setTimeout(showNextTurn, 800);
            } else {
                const p = document.createElement('p');
                p.textContent = data.fim ? "🏆 Dungeon finalizada!" : "💀 Você morreu!";
                p.style.fontWeight = 'bold';
                p.style.color = '#fff';
                logDiv.appendChild(p);
            }
        }

        showNextTurn();
    });
}
</script>

</body>
</html>
