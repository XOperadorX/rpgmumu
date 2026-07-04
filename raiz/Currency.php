<?php
// Conexão com SQL Server
$serverName = "localhost"; // ou "SERVIDOR\INSTANCIA"
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",
    "PWD" => "Xer@x123456"
];

// Cria a conexão
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Verifica erro de conexão
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [ID], [PrecoAtual], [LastUpdate] FROM [MumuDB].[dbo].[Currency]";
$stmt = sqlsrv_query($conn, $sql);

// Verifica erro na query
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe resultados em tabela
echo "<table border='1' cellpadding='6' style='border-collapse:collapse;'>
        <tr style='background:#222;color:#fff;'>
            <th>ID</th>
            <th>Preço Atual</th>
            <th>Última Atualização</th>
        </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = $row['ID'];
    $preco = number_format($row['PrecoAtual'], 2, ',', '.');
    $data = $row['LastUpdate'] instanceof DateTime ? $row['LastUpdate']->format('d/m/Y H:i:s') : '—';
    echo "<tr>
            <td>{$id}</td>
            <td>R$ {$preco}</td>
            <td>{$data}</td>
          </tr>";
}

echo "</table>";

// Fecha conexão
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
