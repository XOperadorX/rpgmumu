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


// Pega personagem do jogador
$stmt = sqlsrv_query($conn, "SELECT TOP 1 * FROM Characters WHERE PlayerID=?", [$playerID]);
$char = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$char){
    die("Você precisa ter pelo menos um personagem. <a href='dashboard.php'>⬅️ Voltar</a>");
}

// Pega 3 inimigos aleatórios do banco
$enemies = [];
$enemyStmt = sqlsrv_query($conn, "
    SELECT TOP 3 EnemyID, Name, HP, MaxHP, XP, Loot, Mana, MaxMana, Level 
    FROM Enemies 
    ORDER BY NEWID()
");
if($enemyStmt){
    while($row = sqlsrv_fetch_array($enemyStmt, SQLSRV_FETCH_ASSOC)){
        // Converte string de Loot (se estiver CSV) em array
        $lootArray = isset($row['Loot']) ? array_map('trim', explode(',', $row['Loot'])) : [];
        $enemies[] = [
            'Name' => $row['Name'],
            'Level'=> $row['Level'],
            'XP'   => $row['XP'],
            'HP'   => $row['HP'],
            'MaxHP'=> $row['MaxHP'],
            'Mana' => $row['Mana'],
            'MaxMana'=> $row['MaxMana'],
            'Loot' => $lootArray
        ];
    }
}

// Inicializa dungeon na sessão
$_SESSION['dungeon'] = [
    'playerID' => $playerID,
    'char' => [
        'CharID' => $char['CharID'],
        'Name'   => $char['Name'],
        'HP'     => $char['HP'],
        'MaxHP'  => $char['MaxHP'],
        'Mana'   => $char['Mana'],
        'MaxMana'=> $char['MaxMana'],
        'Level'  => $char['Level'],
        'Exp'    => $char['Exp']
    ],
    'enemies' => $enemies,
    'current'=>0,
    'log'=>[],
    'loot'=>[],
    'fim'=>false
];

// Redireciona para dungeon
header("Location: dung.php");
exit;
