<?php
// Conexão com o SQL Server
$serverName = "localhost"; // ou IP do servidor
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",
    "PWD" => "Xer@x123456"
];

// Cria a conexão
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Query SQL
$sql = "SELECT TOP 1000 [LogID], [CharID], [Message], [CreatedAt] FROM [dbo].[DungeonLog]";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe os resultados
echo "<table border='1'>";
echo "<tr><th>LogID</th><th>CharID</th><th>Message</th><th>CreatedAt</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['LogID'] . "</td>";
    echo "<td>" . $row['CharID'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Message']) . "</td>";
    echo "<td>" . $row['CreatedAt']->format('Y-m-d H:i:s') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Fecha a conexão
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
