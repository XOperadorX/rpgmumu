<?php
// Conexão com SQL Server
$serverName = "SEU_SERVIDOR"; // Ex: "localhost"
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",
    "PWD" => "Xer@x123456"
];

// Conecta ao SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Consulta SQL
$sql = "SELECT TOP 1000 [EnemyID], [Name], [HP], [MaxHP], [XP], [Loot], [Mana], [MaxMana], [Level]
        FROM [MumuDB].[dbo].[Enemies]";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe os resultados
echo "<table border='1'>";
echo "<tr>
        <th>EnemyID</th>
        <th>Name</th>
        <th>HP</th>
        <th>MaxHP</th>
        <th>XP</th>
        <th>Loot</th>
        <th>Mana</th>
        <th>MaxMana</th>
        <th>Level</th>
      </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['EnemyID']}</td>
            <td>{$row['Name']}</td>
            <td>{$row['HP']}</td>
            <td>{$row['MaxHP']}</td>
            <td>{$row['XP']}</td>
            <td>{$row['Loot']}</td>
            <td>{$row['Mana']}</td>
            <td>{$row['MaxMana']}</td>
            <td>{$row['Level']}</td>
          </tr>";
}

echo "</table>";

// Fecha a conexão
sqlsrv_close($conn);
?>
