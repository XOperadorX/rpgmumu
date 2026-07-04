<?php
if (!isset($conn)) {
    include "db.php"; // Garante que a conexão está disponível
}

if (!isset($_SESSION)) {
    session_start();
}

$playerID = $_SESSION['PlayerID'] ?? null;

if ($playerID) {
    $stmt = sqlsrv_query($conn, "SELECT Banido FROM Players WHERE PlayerID = ?", [$playerID]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (!empty($row['Banido']) && $row['Banido'] == 1) {
            die("⛔ Você está banido e não pode acessar o jogo.");
        }
    }
}


// Pega personagem do player
$stmtChar = sqlsrv_query($conn,"SELECT TOP 1 * FROM Characters WHERE PlayerID=?",[$playerID]);
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
if(!$char){
    echo json_encode(['error'=>'Nenhum personagem encontrado']);
    exit;
}

// Pega moedas
$stmtPlayer = sqlsrv_query($conn,"SELECT MoedaMumu FROM Players WHERE PlayerID=?",[$playerID]);
$playerData = sqlsrv_fetch_array($stmtPlayer,SQLSRV_FETCH_ASSOC);
$moedas = $playerData['MoedaMumu'] ?? 0;

// Preços
$precoHP = 10;
$precoMana = 10;

// Processa requisição AJAX
$tipo = $_POST['tipo'] ?? '';
$mensagem = '';
if($tipo==='hp'){
    if($moedas >= $precoHP){
        $moedas -= $precoHP;
        sqlsrv_query($conn,"UPDATE Characters SET HP=? WHERE CharID=?",[$char['MaxHP'],$char['CharID']]);
        sqlsrv_query($conn,"UPDATE Players SET MoedaMumu=? WHERE PlayerID=?",[$moedas,$playerID]);
        $mensagem = "❤️ HP restaurado com sucesso!";
    } else { $mensagem="Você não tem moedas suficientes para restaurar HP."; }
} elseif($tipo==='mana'){
    if($moedas >= $precoMana){
        $moedas -= $precoMana;
        sqlsrv_query($conn,"UPDATE Characters SET Mana=? WHERE CharID=?",[$char['MaxMana'],$char['CharID']]);
        sqlsrv_query($conn,"UPDATE Players SET MoedaMumu=? WHERE PlayerID=?",[$moedas,$playerID]);
        $mensagem = "🔵 Mana restaurada com sucesso!";
    } else { $mensagem="Você não tem moedas suficientes para restaurar Mana."; }
}

// Atualiza valores do personagem
$stmtChar = sqlsrv_query($conn,"SELECT * FROM Characters WHERE CharID=?",[$char['CharID']]);
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);

echo json_encode([
    'char' => [
        'HP' => (int)$char['HP'],
        'MaxHP' => (int)$char['MaxHP'],
        'Mana' => (int)$char['Mana'],
        'MaxMana' => (int)$char['MaxMana']
    ],
    'moedas' => $moedas,
    'mensagem' => $mensagem
]);
