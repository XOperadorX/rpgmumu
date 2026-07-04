<?php
include "db.php"; // arquivo que contém a conexão $conn com o SQL Server

// Verifica se a conexão está ativa
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta os 1000 registros do histórico bancário
$sql = "SELECT TOP 1000 [HistoryID], [PlayerID], [Tipo], [Valor], [Data]
        FROM [MumuDB].[dbo].[BankHistory]
        ORDER BY [Data] DESC";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe o resultado em uma tabela HTML
echo "<h2>Histórico Bancário</h2>";
echo "<table border='1' cellpadding='5'>
<tr>
    <th>ID</th>
    <th>PlayerID</th>
    <th>Tipo</th>
    <th>Valor</th>
    <th>Data</th>
</tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['HistoryID']}</td>";
    echo "<td>{$row['PlayerID']}</td>";
    echo "<td>{$row['Tipo']}</td>";
    echo "<td>{$row['Valor']}</td>";

    // Formata a data corretamente
    $data = $row['Data'] instanceof DateTime ? $row['Data']->format('d/m/Y H:i:s') : '';
    echo "<td>{$data}</td>";

    echo "</tr>";
}

echo "</table>";

// Libera os recursos
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
