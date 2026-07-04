<?php
// Conexão com SQL Server
$serverName = "localhost"; // ou "SERVIDOR\INSTANCIA"
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa", // seu usuário
    "PWD" => "Xer@x123456", // sua senha
    "CharacterSet" => "UTF-8"
];

// Conecta ao SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [LootID], [EnemyID], [ItemName], [DropChance]
        FROM [MumuDB].[dbo].[EnemyLoot]";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe resultados em tabela HTML
echo "<table border='1' cellspacing='0' cellpadding='5'>";
echo "<tr><th>LootID</th><th>EnemyID</th><th>ItemName</th><th>DropChance (%)</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['LootID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['EnemyID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['ItemName']) . "</td>";
    echo "<td>" . htmlspecialchars($row['DropChance']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Fecha conexão
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
