<?php
$serverName = "localhost";
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",   // troque pelo seu usuário do SQL Server
    "PWD" => "Xer@x123456",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("❌ Conexão falhou: " . print_r(sqlsrv_errors(), true));
}
?>
