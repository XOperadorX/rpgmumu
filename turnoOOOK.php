<?php
session_start();

if(!isset($_SESSION['dungeon'])) exit;
$dungeon = &$_SESSION['dungeon'];
$player = &$dungeon['char'];
$enemy = &$dungeon['enemies'][$dungeon['current']] ?? null;
$log = &$dungeon['log'];

// Combate automático
if($enemy && $player['HP']>0 && $enemy['HP']>0){
    // Jogador ataca
    $danoPlayer = rand(5,15);
    $enemy['HP'] -= $danoPlayer;
    $log[] = "{$player['Name']} causou $danoPlayer de dano em {$enemy['Name']}";
    if($enemy['HP']<=0){
        $log[] = "{$enemy['Name']} foi derrotado!";
        $enemy['HP']=0;
        // Ganha XP
        $player['Exp'] += $enemy['XP'] ?? 10;
        $dungeon['current']++;
        $enemy = &$dungeon['enemies'][$dungeon['current']] ?? null;
    }

    // Inimigo ataca se ainda vivo
    if($enemy && $enemy['HP']>0){
        $danoEnemy = rand(3,12);
        $player['HP'] -= $danoEnemy;
        if($player['HP']<0) $player['HP']=0;
        $log[] = "{$enemy['Name']} causou $danoEnemy de dano em {$player['Name']}";
    }
}

// Verifica fim
if($player['HP']<=0) $dungeon['fim']=true;
if(!$enemy) $dungeon['fim']=true;

header('Content-Type: application/json');
echo json_encode([
    'player'=>$player,
    'enemy'=>$enemy,
    'log'=>$log,
    'fim'=>$dungeon['fim']
]);
