<?php
include "db.php";
header('Content-Type: application/json; charset=utf-8');

// Cria tabela de posições de inimigos se não existir
sqlsrv_query($conn, "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='EnemyPositions' AND xtype='U')
CREATE TABLE EnemyPositions (
    EnemyID INT PRIMARY KEY,
    Xpos INT NOT NULL,
    Ypos INT NOT NULL
);");

// Junta dados básicos de inimigos
$sql = "SELECT TOP 100 e.EnemyID, e.Name, ISNULL(p.Xpos,0) AS Xpos, ISNULL(p.Ypos,0) AS Ypos
        FROM dbo.Enemies e
        LEFT JOIN EnemyPositions p ON e.EnemyID = p.EnemyID";
$res = sqlsrv_query($conn, $sql);


$enemies = [];
$q = sqlsrv_query($conn, "SELECT CharID, Name, Xpos, Ypos, CharSVG FROM dbo.Enemies"); // ou sua tabela de inimigos
while ($r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
    $enemies[] = [
        'id' => $r['CharID'],
        'name' => $r['Name'],
        'x' => intval($r['Xpos']),
        'y' => intval($r['Ypos']),
        'svg' => $r['CharSVG'] ?: "<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='#ff0000'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='#666'/></svg>"
    ];
}
echo json_encode(['enemies'=>$enemies], JSON_UNESCAPED_UNICODE);
