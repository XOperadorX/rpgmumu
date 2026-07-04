<?php
// Configuração da conexão com SQL Server
$serverName = "localhost"; // ou "127.0.0.1", ou "SERVIDOR\INSTANCIA"
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",           // seu usuário do SQL Server
    "PWD" => "Xer@x123456",    // sua senha
];

// Conectar ao SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [ID], [Codigo], [Usado] FROM [MumuDB].[dbo].[Codigos]";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibir resultados
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Código</th><th>Usado</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Codigo']) . "</td>";
    echo "<td>" . ($row['Usado'] ? "Sim" : "Não") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Fechar conexão
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
