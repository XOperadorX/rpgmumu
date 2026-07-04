<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['PlayerID'])) exit;

$player = &$_SESSION['dungeon']['char'];
$itemID = $_POST['itemID'] ?? null;
$item = &$_SESSION['inventario'][$itemID] ?? null;

if(!$item || $item['qtd']<=0) exit(json_encode(['status'=>'erro','mensagem'=>'Item inválido']));

$hp = intval($item['hp']??0);
$mana = intval($item['mana']??0);
$power = intval($item['power']??0);

$player['HP'] = min($player['MaxHP'],$player['HP']+$hp);
$player['Mana'] = min($player['MaxMana'],$player['Mana']+$mana);
$player['Power'] = min($player['MaxPower'],$player['Power']+$power);

$item['qtd'] -= 1;

echo json_encode(['status'=>'ok','novaQtd'=>$item['qtd']]);
