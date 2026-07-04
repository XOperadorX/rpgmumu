<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['success'=>false,'message'=>"⛔ Acesso negado. Faça login."]);
    exit;
}

// Recebe CharID do POST
$input = json_decode(file_get_contents('php://input'), true);
$charID = intval($input['CharID'] ?? 0);

if(!$charID){
    echo json_encode(['success'=>false,'message'=>"⛔ Personagem inválido."]);
    exit;
}

$playerID = $_SESSION['PlayerID'];
$valorRestaurar = 500; // Pix
$cooldownSegundos = 300; // 5 minutos

// Busca atributos atuais e saldo Pix
$sql = "SELECT c.HP, c.MaxHP, c.Mana, c.MaxMana, c.Power, c.Exp, c.Level, b.Pix, c.LastRestore
        FROM Characters c
        JOIN BankAccounts b ON c.PlayerID = b.PlayerID
        WHERE c.PlayerID = ? AND c.CharID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID, $charID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$row){
    echo json_encode(['success'=>false,'message'=>"Erro ao buscar atributos do personagem."]);
    exit;
}

// Verifica cooldown
$lastRestore = $row['LastRestore'] ?? null;
if($lastRestore){
    $lastTime = $lastRestore instanceof DateTime ? $lastRestore : new DateTime($lastRestore);
    $diff = (new DateTime())->getTimestamp() - $lastTime->getTimestamp();
    if($diff < $cooldownSegundos){
        $restante = $cooldownSegundos - $diff;
        echo json_encode(['success'=>false,'message'=>"⏳ Aguarde {$restante} segundos antes de restaurar novamente."]);
        exit;
    }
}

// Verifica se já está completo
$hpFull = $row['HP'] >= $row['MaxHP'];
$manaFull = $row['Mana'] >= $row['MaxMana'];
$powerFull = $row['Power'] >= 100;

if($hpFull && $manaFull && $powerFull){
    echo json_encode([
        'success'=>true,
        'message'=>"⚡ Atributos já estão completos, nenhum gasto necessário!",
        'HP'=>$row['HP'],
        'MaxHP'=>$row['MaxHP'],
        'Mana'=>$row['Mana'],
        'MaxMana'=>$row['MaxMana'],
        'Power'=>$row['Power'],
        'Exp'=>$row['Exp'],
        'Level'=>$row['Level'],
        'Pix'=>$row['Pix']
    ]);
    exit;
}

// Verifica saldo Pix
if(($row['Pix'] ?? 0) < $valorRestaurar){
    echo json_encode(['success'=>false,'message'=>"⛔ Saldo insuficiente. Você precisa de $valorRestaurar Pix."]);
    exit;
}

// Deduz Pix
sqlsrv_query($conn, "UPDATE BankAccounts SET Pix = Pix - ? WHERE PlayerID = ?", [$valorRestaurar, $playerID]);

// Restaura atributos e registra LastRestore
sqlsrv_query($conn, "UPDATE Characters SET HP = MaxHP, Mana = MaxMana, Power = 100, LastRestore = GETDATE() WHERE CharID = ? AND PlayerID = ?", [$charID, $playerID]);

// Busca atributos atualizados
$sql = "SELECT c.HP, c.MaxHP, c.Mana, c.MaxMana, c.Power, c.Exp, c.Level, b.Pix
        FROM Characters c
        JOIN BankAccounts b ON c.PlayerID = b.PlayerID
        WHERE c.PlayerID = ? AND c.CharID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID, $charID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Atualiza sessão
$_SESSION['char'][$charID] = [
    'HP'=>$row['HP'],
    'MaxHP'=>$row['MaxHP'],
    'Mana'=>$row['Mana'],
    'MaxMana'=>$row['MaxMana'],
    'Power'=>$row['Power'],
    'Exp'=>$row['Exp'],
    'Level'=>$row['Level']
];

// Retorna sucesso
echo json_encode([
    'success'=>true,
    'message'=>"✅ Atributos restaurados por $valorRestaurar Pix!",
    'HP'=>$row['HP'],
    'MaxHP'=>$row['MaxHP'],
    'Mana'=>$row['Mana'],
    'MaxMana'=>$row['MaxMana'],
    'Power'=>$row['Power'],
    'Exp'=>$row['Exp'],
    'Level'=>$row['Level'],
    'Pix'=>$row['Pix']
]);
