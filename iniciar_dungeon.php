<?php
session_start();
include "db.php";
include "check_ban.php"; // Verifica se jogador está banido

if (!isset($_SESSION['PlayerID'])) {
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Inicializa loot do jogador se ainda não existir
if (!isset($_SESSION['loot'])) {
    $_SESSION['loot'] = [];
}

// Limpa dungeon antiga
$_SESSION['dungeon'] = [
    'enemies' => [],
    'current' => 0,
    'fim' => false
];

// Pega inimigos com loot de uma só vez
$sql = "
    SELECT 
        e.EnemyID, e.Name, e.HP, e.MaxHP, e.Level, 
        l.ItemName, l.Rarity, l.ChanceFinal
    FROM Enemies e
    LEFT JOIN EnemyLoot l ON e.EnemyID = l.EnemyID
";

$stmt = sqlsrv_query($conn, $sql);
if (!$stmt) {
    die("Erro ao carregar inimigos: " . print_r(sqlsrv_errors(), true));
}

// Funções para stats progressivos
function calcHP($baseHP, $level) {
    // HP cresce exponencial leve: HP = baseHP * (1.1 ^ (level - 1))
    return round($baseHP * pow(1.1, $level - 1));
}

function calcPower($level) {
    // Power escala logarítmica: log base 2, normalizado para 0-100
    $power = log($level + 1, 2) * 20; // level 1 ~20, level 5 ~46, level 10 ~69
    return min(100, round($power));
}

function calcDamage($level) {
    // Dano base exponencial leve
    return round(5 * pow(1.15, $level - 1));
}

// Agrupa loot por inimigo e adiciona stats baseados no level
$enemies = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $enemyID = $row['EnemyID'];

    if (!isset($enemies[$enemyID])) {
        $level = (int)$row['Level'];
        $baseMaxHP = (int)$row['MaxHP'] ?: 50; // fallback caso MaxHP seja 0

        $maxHP = calcHP($baseMaxHP, $level);
        $hp = min($row['HP'], $maxHP); // não ultrapassa o max
        $hpBar = ($maxHP > 0) ? round($hp / $maxHP * 100) : 0;

        $enemies[$enemyID] = [
            'EnemyID' => $enemyID,
            'Name' => $row['Name'],
            'Level' => $level,
            'HP' => $hp,
            'MaxHP' => $maxHP,
            'HPBarPercent' => $hpBar,
            'Power' => calcPower($level),
            'BaseDamage' => calcDamage($level),
            'Loot' => []
        ];
    }

    // Adiciona loot se existir
    if (!empty($row['ItemName'])) {
        $enemies[$enemyID]['Loot'][] = [
            'ItemName' => $row['ItemName'],
            'Rarity' => $row['Rarity'],
            'ChanceFinal' => $row['ChanceFinal']
        ];
    }
}

// Adiciona inimigos à sessão
$_SESSION['dungeon']['enemies'] = array_values($enemies);

echo "Dungeon inicializada com sucesso! Você tem " . count($_SESSION['dungeon']['enemies']) . " inimigos.";
?>
