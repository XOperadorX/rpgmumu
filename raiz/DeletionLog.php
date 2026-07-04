<?php
// Configurações de conexão
$serverName = "localhost"; // ou IP do servidor SQL
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",   // substitua pelo usuário do SQL Server
    "PWD" => "Xer@x123456",     // substitua pela senha
    "CharacterSet" => "UTF-8"
];

// Conectando ao SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [LogID], [PlayerID], [DeletedAt], [Reason] FROM [MumuDB].[dbo].[DeletionLog]";

// Executa a consulta
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe os resultados
echo "<h3>🗑️ Registros da Tabela DeletionLog</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>LogID</th><th>PlayerID</th><th>DeletedAt</th><th>Reason</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['LogID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['PlayerID']) . "</td>";
    echo "<td>" . ($row['DeletedAt'] instanceof DateTime ? $row['DeletedAt']->format('Y-m-d H:i:s') : 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['Reason']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Fecha a conexão
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
