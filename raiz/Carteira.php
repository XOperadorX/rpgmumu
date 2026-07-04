<?php
include "db.php"; // conexão SQLSRV (verifique se já está configurado corretamente)

// Consulta SQL
$sql = "SELECT TOP 1000 [CarteiraID], [PlayerID], [AtivoID], [Quantidade], [PrecoMedio] FROM [MumuDB].[dbo].[Carteira]";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "<h2>📊 Carteira de Investimentos</h2>";
echo "<table border='1' cellspacing='0' cellpadding='8'>";
echo "<tr>
        <th>CarteiraID</th>
        <th>PlayerID</th>
        <th>AtivoID</th>
        <th>Quantidade</th>
        <th>Preço Médio</th>
      </tr>";

// Exibir resultados
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['CarteiraID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['PlayerID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['AtivoID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Quantidade']) . "</td>";
    echo "<td>" . htmlspecialchars($row['PrecoMedio']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Liberar recursos
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
