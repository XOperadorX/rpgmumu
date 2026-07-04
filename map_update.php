<?php
include "db.php";

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT c.CharID, c.PlayerID, c.Name, c.Class, c.Level, c.Exp, c.HP, c.Mana, p.Xpos, p.Ypos
        FROM dbo.Characters c
        JOIN CharacterPositions p ON c.PlayerID = p.PlayerID AND c.CharID = p.CharID
        WHERE p.Xpos IS NOT NULL AND p.Ypos IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);

$result = [];
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $result[] = $row;
    }
}

echo json_encode($result);
?>
