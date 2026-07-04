<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['error'=>'Acesso negado']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// Dungeon da sessão
if(!isset($_SESSION['dungeon'])){
    echo json_encode(['error'=>'Dungeon não iniciada']);
    exit;
}

$dungeon = &$_SESSION['dungeon'];
$char = &$dungeon['char'];
$current = $dungeon['current'];
$enemy = &$dungeon['enemies'][$current] ?? null;
$log = &$dungeon['log'];
$inventario = $_SESSION['inventario'] ?? [];

// Se não houver inimigo, dungeon acabou
if(!$enemy){
    $dungeon['fim'] = true;
    echo json_encode(['fim'=>true]);
    exit;
}

// ==========================
// Função de dano
// ==========================
function dano($atk,$def){
    $d = $atk - intval($def/2);
    return max(1,$d);
}

// ==========================
// Função de XP e level-up
// ==========================
function verificarLevelUp(&$char, &$log, $conn){
    $levelup = false;
    while($char['Exp'] >= ($char['NextLevelExp'] ?? 100)){
        $char['Exp'] -= $char['NextLevelExp'];
        $char['Level'] += 1;
        $levelup = true;
        // Aumenta atributos ao subir de nível
        $char['MaxHP'] += 10;
        $char['HP'] = $char['MaxHP'];
        $char['MaxMana'] += 5;
        $char['Mana'] = $char['MaxMana'];
        $char['Power'] += 5;
        $char['MaxPower'] += 5;
        // Próximo nível XP
        $char['NextLevelExp'] = intval(($char['NextLevelExp'] ?? 100) * 1.5);
        $log[] = ['msg'=>"⬆️ Parabéns! Você subiu para Level {$char['Level']}!"];
    }
    if($levelup){
        // Atualiza no banco
        sqlsrv_query($conn,"UPDATE Characters SET Level=?, HP=?, MaxHP=?, Mana=?, MaxMana=?, Power=?, MaxPower=?, Exp=?, NextLevelExp=? WHERE CharID=?",
            [$char['Level'],$char['HP'],$char['MaxHP'],$char['Mana'],$char['MaxMana'],$char['Power'],$char['MaxPower'],$char['Exp'],$char['NextLevelExp'],$char['CharID']]);
    }
}

// ==========================
// Turno do jogador
// ==========================
if($char['HP'] > 0 && $enemy['HP'] > 0){
    $danoPlayer = dano($char['Attack'] ?? 5, $enemy['Defense'] ?? 0);
    $enemy['HP'] -= $danoPlayer;
    if($enemy['HP'] < 0) $enemy['HP'] = 0;
    $log[] = ['msg'=>"Você causou <b>$danoPlayer</b> de dano em {$enemy['Name']}!"];

    if($enemy['HP'] <= 0){
        $log[] = ['msg'=>"✅ Você derrotou {$enemy['Name']}!"];
        // Dá XP
        $char['Exp'] += intval($enemy['XP'] ?? 0);
        $log[] = ['msg'=>"🎉 Você ganhou {$enemy['XP']} XP!"];
        verificarLevelUp($char, $log, $conn);

        // Dá loot
        foreach($enemy['Loot'] as $itemID){
            if(isset($inventario[$itemID])) $inventario[$itemID]['qtd']++;
            else $inventario[$itemID] = ['nome'=>$itemID,'qtd'=>1,'valor'=>0];
            $log[] = ['msg'=>"📦 Você pegou item: $itemID"];
        }

        // Próximo inimigo
        $dungeon['current']++;
        if(!isset($dungeon['enemies'][$dungeon['current']])){
            $dungeon['fim'] = true;
        }
    }
}

// ==========================
// Turno do inimigo
// ==========================
if($enemy && $enemy['HP'] > 0 && $char['HP'] > 0){
    $danoEnemy = dano($enemy['Attack'] ?? 5, $char['Defense'] ?? 0);
    $char['HP'] -= $danoEnemy;
    if($char['HP'] < 0) $char['HP'] = 0;
    $log[] = ['msg'=>"💀 {$enemy['Name']} causou <b>$danoEnemy</b> de dano em você!"];
}

// ==========================
// Atualiza banco
// ==========================
sqlsrv_query($conn,"UPDATE Characters SET HP=?, Exp=? WHERE CharID=?",
    [$char['HP'],$char['Exp'],$char['CharID']]);

// ==========================
// Atualiza inventário na sessão
// ==========================
$_SESSION['inventario'] = $inventario;

// ==========================
// Retorno JSON
// ==========================
echo json_encode([
    'char'=>$char,
    'enemy'=>$enemy,
    'log'=>$log,
    'inventario'=>$inventario,
    'fim'=>$dungeon['fim'] ?? false
]);
