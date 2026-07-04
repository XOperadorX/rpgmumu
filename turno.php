<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: application/json');

function logMsg(&$log, $msg, $color='black'){
    $hora = date('d/m/Y H:i:s');
    $log[] = "<span style='color:{$color};'>[$hora] {$msg}</span>";
}

function calcularDano($nivel,$min,$max){
    return rand($min,$max)+$nivel;
}

function usarMagia(&$char,&$enemy,$magia,&$log){
    $char['Mana'] -= $magia['mana'];
    if($magia['tipo']==='ataque'){
        $dano = calcularDano($char['Level'],$magia['dano'][0],$magia['dano'][1]);
        $enemy['HP']=max(0,$enemy['HP']-$dano);
        logMsg($log,"{$char['Name']} conjurou <b>{$magia['nome']}</b> causando {$dano} de dano em {$enemy['Name']}!",'orange');
    } elseif($magia['tipo']==='cura'){
        $cura = rand($magia['cura'][0],$magia['cura'][1]);
        $char['HP']=min($char['MaxHP'],$char['HP']+$cura);
        logMsg($log,"{$char['Name']} usou <b>{$magia['nome']}</b> e recuperou {$cura} HP!",'deepskyblue');
    }
}

function atualizarPersonagemDB($conn,$char){
    $stmt = sqlsrv_prepare($conn,"UPDATE Characters SET HP=?, Mana=?, Power=?, Exp=?, Level=?, MaxHP=?, MaxMana=?, MaxPower=? WHERE CharID=?",
        [$char['HP'],$char['Mana'],$char['Power'],$char['Exp'],$char['Level'],$char['MaxHP'],$char['MaxMana'],$char['MaxPower'],$char['CharID']]);
    if($stmt!==false) sqlsrv_execute($stmt);
}

// ===== Verificações iniciais =====
if(!isset($_SESSION['dungeon']) || !is_array($_SESSION['dungeon'])){
    echo json_encode(['fim'=>true,'msg'=>'A dungeon não está ativa.','voltar'=>true],JSON_UNESCAPED_UNICODE);
    exit;
}

$dungeon =& $_SESSION['dungeon'];
if(!isset($dungeon['char'],$dungeon['playerID'])){
    echo json_encode(['fim'=>true,'msg'=>'Dungeon inválida.','voltar'=>true],JSON_UNESCAPED_UNICODE);
    exit;
}

$char =& $dungeon['char'];
$currentIndex = $dungeon['current'] ?? 0;
$currentEnemy =& $dungeon['enemies'][$currentIndex] ?? null;
$log=[];

// ==========================
// Inventário atualizado (banco + sessão)
// ==========================
$playerID = $dungeon['playerID'];
$inventario = [];

// Puxa do banco
$stmtItens = sqlsrv_query($conn, "SELECT ItemID, Nome, Quantidade, Raridade, Valor, Tipo FROM dbo.Items WHERE PlayerID = ?", [$playerID]);
if($stmtItens){
    while($row = sqlsrv_fetch_array($stmtItens, SQLSRV_FETCH_ASSOC)){
        $inventario[$row['ItemID']] = [
            'nome' => $row['Nome'],
            'qtd' => intval($row['Quantidade']),
            'raridade' => strtolower($row['Raridade'] ?? 'comum'),
            'valor' => intval($row['Valor'] ?? 10),
            'tipo' => $row['Tipo']
        ];
    }
}

// Mescla com sessão (drops recentes)
if(isset($_SESSION['inventario'])){
    foreach($_SESSION['inventario'] as $nome => $qtd){
        $encontrado = false;
        foreach($inventario as $id => &$item){
            if($item['nome'] === $nome){
                $item['qtd'] += $qtd;
                $encontrado = true;
                break;
            }
        }
        if(!$encontrado){
            $tempID = -(count($inventario) + 1);
            $inventario[$tempID] = [
                'nome' => $nome,
                'qtd' => $qtd,
                'raridade' => 'comum',
                'valor' => 50,
                'tipo' => 'outro'
            ];
        }
    }
}

// ===== Jogador morto =====
if($char['HP']<=0){
    logMsg($log,"💀 Você morreu! Dungeon encerrada.");
    $dungeon['fim']=true;
    unset($_SESSION['dungeon']);
    echo json_encode(['char'=>$char,'enemy'=>null,'log'=>$log,'fim'=>true,'voltar'=>true,'inventario'=>$inventario],JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== Dungeon acabada =====
if(!$currentEnemy){
    logMsg($log,"🏁 Todos os inimigos foram derrotados!");
    $dungeon['fim']=true;
    unset($_SESSION['dungeon']);
    echo json_encode(['char'=>$char,'enemy'=>null,'log'=>$log,'fim'=>true,'voltar'=>true,'inventario'=>$inventario],JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== Campos padrão =====
$char += ['Level'=>1,'HP'=>100,'MaxHP'=>100,'Mana'=>50,'MaxMana'=>50,'Exp'=>0,'Power'=>0,'MaxPower'=>0,'Gold'=>0];
$currentEnemy += ['Level'=>1,'HP'=>1,'MaxHP'=>1,'Mana'=>30,'MaxMana'=>30,'ExpReward'=>5,'GoldReward'=>10];

// ===== Garantir NextLevelXP =====
if(!isset($char['NextLevelXP']) || $char['NextLevelXP']<=0){
    $char['NextLevelXP'] = $char['Level']*50;
}

// ===== Magias =====
$magias = [
    ['nome'=>'🔥 Bola de Fogo','mana'=>15,'dano'=>[1,2],'tipo'=>'ataque'],
    ['nome'=>'⚡ Raio Místico','mana'=>25,'dano'=>[3,4],'tipo'=>'ataque'],
    ['nome'=>'💧 Cura Espiritual','mana'=>20,'cura'=>[5,6],'tipo'=>'cura']
];

// ===== Ação =====
$acao='ataque';
$magiaUsada=null;
$magiasDisponiveis=array_filter($magias,fn($m)=>$char['Mana']>=$m['mana']);
if(!empty($magiasDisponiveis) && rand(0,100)<60){
    $magiaUsada = $magiasDisponiveis[array_rand($magiasDisponiveis)];
    $acao='magia';
}

if($acao==='magia' && $magiaUsada){
    usarMagia($char,$currentEnemy,$magiaUsada,$log);
}else{
    $damage = calcularDano($char['Level'],5,10);
    $currentEnemy['HP']=max(0,$currentEnemy['HP']-$damage);
    logMsg($log,"{$char['Name']} atacou {$currentEnemy['Name']} causando {$damage} de dano. HP do inimigo: {$currentEnemy['HP']}/{$currentEnemy['MaxHP']}",'blue');
}

atualizarPersonagemDB($conn,$char);

// ===== Ataque inimigo =====
if($currentEnemy['HP']>0){
    $inimigoUsaMagia=($currentEnemy['Mana']>=15 && rand(0,100)<50);
    if($inimigoUsaMagia){
        $currentEnemy['Mana']-=15;
        $damageEnemy=calcularDano($currentEnemy['Level'],12,25);
        $char['HP']=max(0,$char['HP']-$damageEnemy);
        logMsg($log,"{$currentEnemy['Name']} lançou Magia Sombria causando {$damageEnemy} de dano!",'red');
    }else{
        $damageEnemy=calcularDano($currentEnemy['Level'],3,8);
        $char['HP']=max(0,$char['HP']-$damageEnemy);
        logMsg($log,"{$currentEnemy['Name']} atacou causando {$damageEnemy} de dano.",'red');
    }
    atualizarPersonagemDB($conn,$char);
}

// ===== Inimigo derrotado =====
if($currentEnemy['HP']<=0){
    logMsg($log,"✅ Você derrotou {$currentEnemy['Name']}! Ganhou {$currentEnemy['ExpReward']} XP e {$currentEnemy['GoldReward']} moedas.",'green');
    $char['Exp']+=$currentEnemy['ExpReward'];
    $char['Gold']+=$currentEnemy['GoldReward'];

    while($char['Exp'] >= $char['NextLevelXP']){
        $char['Exp'] -= $char['NextLevelXP'];
        $char['Level']++;
        $char['MaxHP']+=10; $char['HP']=$char['MaxHP'];
        $char['MaxMana']+=5; $char['Mana']=$char['MaxMana'];
        $char['MaxPower']+=3; $char['Power']=$char['MaxPower'];
        $char['NextLevelXP'] = $char['Level']*50;
        logMsg($log,"🎉 Parabéns! Você subiu para o nível {$char['Level']}!",'gold');
    }

    // Drops do banco
    $stmtPool = sqlsrv_query($conn,"SELECT TOP 1000 Nome FROM dbo.Items ORDER BY NEWID()");
    if($stmtPool){
        $pool=[];
        while($row = sqlsrv_fetch_array($stmtPool,SQLSRV_FETCH_ASSOC)) $pool[]=$row['Nome'];
        $numDrops=rand(1,3);
        $dropItems=array_slice($pool,0,$numDrops);
        foreach($dropItems as $item){
            $stmtItem = sqlsrv_prepare($conn,"INSERT INTO Items (PlayerID, CharID, Nome, Quantidade) VALUES (?,?,?,?)",
                [$dungeon['playerID'],$char['CharID'],$item,1]);
            if($stmtItem!==false) sqlsrv_execute($stmtItem);

            if(isset($_SESSION['inventario'][$item])) $_SESSION['inventario'][$item]+=1;
            else $_SESSION['inventario'][$item]=1;

            logMsg($log,"🎁 Você pegou: $item",'orange');
        }
    }

    $dungeon['current']++;
    if($dungeon['current']>=count($dungeon['enemies'])){
        logMsg($log,"🏁 Todos os inimigos foram derrotados!");
        $dungeon['fim']=true;
    }
}


$_SESSION['log'][] = [
  'hora' => date('d/m/Y H:i:s'),
  'msg'  => 'Você causou 10 de dano no inimigo!'
];



atualizarPersonagemDB($conn,$char);

echo json_encode([
    'char'=>$char,
    'enemy'=>$dungeon['enemies'][$dungeon['current']]??null,
    'log'=>$log,
    'fim'=>$dungeon['fim']??false,
    'voltar'=>!empty($dungeon['fim']),
    'inventario'=>$inventario
],JSON_UNESCAPED_UNICODE);

exit;
?>
