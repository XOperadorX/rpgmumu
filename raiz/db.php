<?php
$serverName = "localhost";
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",
    "PWD" => "Xer@x123456"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
?>
