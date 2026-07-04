<?php
// Conexão com SQL Server
$serverName = "localhost"; // ou IP/nome do servidor
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa", // usuário do SQL Server
    "PWD" => "Xer@x123456", // senha
];

// Tenta conectar
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [PlantioID], [PlayerID], [FrutaID], [Quantidade], [PlantadoEm], [Colhido]
        FROM [MumuDB].[dbo].[Fazenda]";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe os resultados em tabela HTML
echo "<table border='1' cellpadding='6' style='border-collapse: collapse;'>
        <tr style='background-color:#ddd;'>
            <th>PlantioID</th>
            <th>PlayerID</th>
            <th>FrutaID</th>
            <th>Quantidade</th>
            <th>PlantadoEm</th>
            <th>Colhido</th>
        </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['PlantioID']}</td>";
    echo "<td>{$row['PlayerID']}</td>";
    echo "<td>{$row['FrutaID']}</td>";
    echo "<td>{$row['Quantidade']}</td>";
    echo "<td>" . ($row['PlantadoEm'] instanceof DateTime ? $row['PlantadoEm']->format('Y-m-d H:i:s') : '') . "</td>";
    echo "<td>" . ($row['Colhido'] ? '✅ Sim' : '❌ Não') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Fecha conexão
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
