<?php
$sql = "SELECT HP, MaxHP FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, array($playerID));
$hp = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "<p>HP: {$hp['HP']}/{$hp['MaxHP']}</p>";
