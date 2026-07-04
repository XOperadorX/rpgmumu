<?php
$serverName = "localhost"; 
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",   // troque pelo seu usuário do SQL Server
    "PWD" => "Xer@x123456"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if(!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
?>
