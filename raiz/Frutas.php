<?php
// Configurações de conexão
$serverName = "localhost"; // ou IP/Nome do servidor SQL
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",
    "PWD" => "Xer@x123456"
];

// Conectando ao SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Verifica se a conexão foi bem-sucedida
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [FrutaID], [Nome], [TempoCrescimento], [PrecoCompra], [PrecoVenda], [PrecoSemente]
        FROM [MumuDB].[dbo].[Frutas]";

$stmt = sqlsrv_query($conn, $sql);

// Verifica se a query deu certo
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe os resultados em uma tabela HTML
echo "<h2>Lista de Frutas 🌱</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0'>
        <tr style='background-color:#222; color:white;'>
            <th>FrutaID</th>
            <th>Nome</th>
            <th>Tempo de Crescimento</th>
            <th>Preço de Compra</th>
            <th>Preço de Venda</th>
            <th>Preço da Semente</th>
        </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['FrutaID']}</td>
            <td>{$row['Nome']}</td>
            <td>{$row['TempoCrescimento']}</td>
            <td>{$row['PrecoCompra']}</td>
            <td>{$row['PrecoVenda']}</td>
            <td>{$row['PrecoSemente']}</td>
          </tr>";
}

echo "</table>";

// Libera recursos e fecha conexão
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
