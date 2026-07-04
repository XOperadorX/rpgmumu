<?php
// Conexão com SQL Server
$serverName = "localhost"; // ou "NOME_DO_SERVIDOR\SQLEXPRESS"
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",         // seu usuário do SQL Server
    "PWD" => "Xer@x123456",  // sua senha
    "CharacterSet" => "UTF-8"
];

// Conectar
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [InventarioID],
               [CharID],
               [Arma],
               [Escudo],
               [Capacete],
               [Armadura],
               [Luva],
               [Calca]
        FROM [MumuDB].[dbo].[Equipamento]";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibir resultados em tabela HTML
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>
        <tr>
            <th>InventarioID</th>
            <th>CharID</th>
            <th>Arma</th>
            <th>Escudo</th>
            <th>Capacete</th>
            <th>Armadura</th>
            <th>Luva</th>
            <th>Calca</th>
        </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['InventarioID']}</td>
            <td>{$row['CharID']}</td>
            <td>{$row['Arma']}</td>
            <td>{$row['Escudo']}</td>
            <td>{$row['Capacete']}</td>
            <td>{$row['Armadura']}</td>
            <td>{$row['Luva']}</td>
            <td>{$row['Calca']}</td>
          </tr>";
}

echo "</table>";

// Fechar conexão
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
