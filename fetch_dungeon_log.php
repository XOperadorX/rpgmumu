<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado");
}

$playerID = $_SESSION['PlayerID'];
$charID = $_GET['CharID'] ?? null;
$itemTypeFilter = $_GET['ItemType'] ?? null;

// Detecta colunas existentes na tabela DungeonLog
$cols = [];
$sqlCols = "SELECT COLUMN_NAME, DATA_TYPE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'DungeonLog'";
$stmtCols = sqlsrv_query($conn, $sqlCols);
while($col = sqlsrv_fetch_array($stmtCols, SQLSRV_FETCH_ASSOC)){
    $cols[strtoupper($col['COLUMN_NAME'])] = $col['DATA_TYPE'];
}

// Define colunas opcionais
$hasDate = false;
$hasItemType = false;

foreach($cols as $name => $type){
    if(in_array(strtoupper($type), ['DATETIME','DATETIME2','SMALLDATETIME','DATE','TIME']) && !$hasDate){
        $dateCol = $name;
        $hasDate = true;
    }
    if(strtoupper($name) == 'ITEMTYPE'){
        $itemTypeCol = $name;
        $hasItemType = true;
    }
}

// Query base
$sql = "SELECT dl.*, c.Name, c.Class, c.Level, c.Exp, c.HP
        FROM DungeonLog dl
        JOIN Characters c ON dl.CharID = c.CharID
        WHERE c.PlayerID = ?";
$params = [$playerID];

// Filtro por personagem
if($charID){
    $sql .= " AND c.CharID = ?";
    $params[] = $charID;
}

// Filtro por ItemType (somente se coluna existir)
if($itemTypeFilter && $hasItemType){
    $sql .= " AND dl.$itemTypeCol = ?";
    $params[] = $itemTypeFilter;
}

// Ordena por data/hora se existir, senão por LogID
if($hasDate){
    $sql .= " ORDER BY dl.$dateCol DESC";
}else{
    // Ajuste 'LogID' para o nome correto da PK se for diferente
    $sql .= " ORDER BY dl.LogID DESC";
}

$stmt = sqlsrv_query($conn, $sql, $params);
if($stmt === false){
    die(print_r(sqlsrv_errors(), true));
}

// Monta tabela
echo "<table style='width:100%; border-collapse: collapse; margin-top:20px;'>
        <tr style='background:#333; color:#fff;'>
            <th>Personagem</th>
            <th>Classe</th>
            <th>Level</th>
            <th>Exp</th>
            <th>HP</th>
            <th>Dungeon</th>
            <th>Item</th>";

if($hasDate){
    echo "<th>Data/Hora</th>";
}

echo "</tr>";

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    echo "<tr style='border:1px solid #444; text-align:center;'>
            <td>{$row['Name']}</td>
            <td>{$row['Class']}</td>
            <td>{$row['Level']}</td>
            <td>{$row['Exp']}</td>
            <td>{$row['HP']}</td>
            <td>{$row['DungeonNome']}</td>
            <td>{$row['ItemNome']}</td>";

    if($hasDate){
        $dataHora = isset($row[$dateCol]) && $row[$dateCol] ? $row[$dateCol]->format('d/m/Y H:i:s') : '';
        echo "<td>{$dataHora}</td>";
    }

    echo "</tr>";
}

echo "</table>";
