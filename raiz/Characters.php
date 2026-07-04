<?php
// Conexão com o banco de dados SQL Server
$serverName = "localhost"; // ou "127.0.0.1", ou "NOMESERVIDOR\SQLEXPRESS"
$connectionOptions = array(
    "Database" => "MumuDB",
    "Uid" => "sa",            // Usuário do SQL Server
    "PWD" => "Xer@x123456", // Senha do SQL Server
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [CharID], [PlayerID], [Name], [Class], [Level], [Exp], [HP], [Mana], [MaxMana], [MaxHP], [Power], [MaxPower]
        FROM [MumuDB].[dbo].[Characters]";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibição dos dados em uma tabela HTML
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>
        <th>CharID</th>
        <th>PlayerID</th>
        <th>Name</th>
        <th>Class</th>
        <th>Level</th>
        <th>Exp</th>
        <th>HP</th>
        <th>Mana</th>
        <th>MaxMana</th>
        <th>MaxHP</th>
        <th>Power</th>
        <th>MaxPower</th>
      </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['CharID']}</td>";
    echo "<td>{$row['PlayerID']}</td>";
    echo "<td>{$row['Name']}</td>";
    echo "<td>{$row['Class']}</td>";
    echo "<td>{$row['Level']}</td>";
    echo "<td>{$row['Exp']}</td>";
    echo "<td>{$row['HP']}</td>";
    echo "<td>{$row['Mana']}</td>";
    echo "<td>{$row['MaxMana']}</td>";
    echo "<td>{$row['MaxHP']}</td>";
    echo "<td>{$row['Power']}</td>";
    echo "<td>{$row['MaxPower']}</td>";
    echo "</tr>";
}

echo "</table>";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
