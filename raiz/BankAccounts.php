<?php
include "db.php"; // conexão com o SQL Server

// Consulta: buscar até 1000 registros de BankAccounts
$sql = "SELECT TOP 1000 
            AccountID,
            PlayerID,
            Corrente,
            Poupanca,
            Pix,
            Real,
            LastInterest,
            LastUpdate,
            Investimento
        FROM MumuDB.dbo.BankAccounts";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "<h2>🏦 Contas Bancárias</h2>";
echo "<table border='1' cellspacing='0' cellpadding='5'>";
echo "<tr>
        <th>AccountID</th>
        <th>PlayerID</th>
        <th>Corrente</th>
        <th>Poupança</th>
        <th>Pix</th>
        <th>Real</th>
        <th>Investimento</th>
        <th>Último Juros</th>
        <th>Última Atualização</th>
      </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['AccountID'] . "</td>";
    echo "<td>" . $row['PlayerID'] . "</td>";
    echo "<td>" . number_format($row['Corrente'], 2, ',', '.') . "</td>";
    echo "<td>" . number_format($row['Poupanca'], 2, ',', '.') . "</td>";
    echo "<td>" . number_format($row['Pix'], 2, ',', '.') . "</td>";
    echo "<td>" . number_format($row['Real'], 2, ',', '.') . "</td>";
    echo "<td>" . number_format($row['Investimento'], 2, ',', '.') . "</td>";
    echo "<td>" . ($row['LastInterest'] ? $row['LastInterest']->format('d/m/Y H:i') : '-') . "</td>";
    echo "<td>" . ($row['LastUpdate'] ? $row['LastUpdate']->format('d/m/Y H:i') : '-') . "</td>";
    echo "</tr>";
}

echo "</table>";

sqlsrv_free_stmt($stmt);
?>