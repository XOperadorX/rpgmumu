<?php
session_start();
include "db.php";
// include "check_ban.php";

// Verifica se veio um POST válido
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['EnemyID'])) {
    die("Requisição inválida.");
}

$enemyID = intval($_POST['EnemyID']);
$name = $_POST['Name'] ?? '';
$level = intval($_POST['Level'] ?? 1);
$xp = intval($_POST['XP'] ?? 0);
$hp = intval($_POST['HP'] ?? 1);
$maxHP = intval($_POST['MaxHP'] ?? 1);
$mana = intval($_POST['Mana'] ?? 0);
$maxMana = intval($_POST['MaxMana'] ?? 0);

// Começa uma transação para evitar dados inconsistentes
sqlsrv_begin_transaction($conn);

// 1️⃣ Atualiza os dados básicos do inimigo
$sql = "UPDATE Enemies SET Name = ?, Level = ?, XP = ?, HP = ?, MaxHP = ?, Mana = ?, MaxMana = ? WHERE EnemyID = ?";
$params = [$name, $level, $xp, $hp, $maxHP, $mana, $maxMana, $enemyID];
$stmt = sqlsrv_query($conn, $sql, $params);

if (!$stmt) {
    sqlsrv_rollback($conn);
    die("Erro ao atualizar inimigo: " . print_r(sqlsrv_errors(), true));
}

// 2️⃣ Atualiza o loot
// Apaga os loot antigos
$sqlDelete = "DELETE FROM EnemyLoot WHERE EnemyID = ?";
$stmtDel = sqlsrv_query($conn, $sqlDelete, [$enemyID]);
if (!$stmtDel) {
    sqlsrv_rollback($conn);
    die("Erro ao limpar loot antigo: " . print_r(sqlsrv_errors(), true));
}

// Insere os loot novos
if (!empty($_POST['Loot'][$enemyID]) && is_array($_POST['Loot'][$enemyID])) {
    $lootArray = $_POST['Loot'][$enemyID];
    $sqlInsert = "INSERT INTO EnemyLoot (EnemyID, ItemName) VALUES (?, ?)";
    foreach ($lootArray as $item) {
        $item = trim($item);
        if ($item === '') continue; // ignora campos vazios
        $stmtInsert = sqlsrv_query($conn, $sqlInsert, [$enemyID, $item]);
        if (!$stmtInsert) {
            sqlsrv_rollback($conn);
            die("Erro ao inserir loot: " . print_r(sqlsrv_errors(), true));
        }
    }
}

// Confirma transação
sqlsrv_commit($conn);

// Redireciona de volta para a página de edição
header("Location: edit_enemies.php");
exit;
?>
