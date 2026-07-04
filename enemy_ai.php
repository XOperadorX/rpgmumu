<?php
include "db.php";

$map = [
    '###############',
    '#.............#',
    '#..###...##...#',
    '#..#.#...##...#',
    '#..#.#.......##',
    '#..###.#####..#',
    '#.............#',
    '#..####..T....#',
    '#..#..#..T....#',
    '#..#..#.......#',
    '#..####.......#',
    '#.............#',
    '###############',
];
$rows = count($map);
$cols = strlen($map[0]);
function isWalkable($map,$x,$y){global $rows,$cols;return !($x<0||$y<0||$x>=$cols||$y>=$rows||$map[$y][$x]=='#'||$map[$y][$x]=='T');}

$res = sqlsrv_query($conn, "SELECT EnemyID,Xpos,Ypos FROM EnemyPositions");
while ($en = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $x = intval($en['Xpos']); $y = intval($en['Ypos']); $id = intval($en['EnemyID']);
    $dirs = [[1,0],[-1,0],[0,1],[0,-1]]; shuffle($dirs);
    foreach ($dirs as $d) {
        $nx=$x+$d[0];$ny=$y+$d[1];
        if (isWalkable($map,$nx,$ny)) {
            sqlsrv_query($conn, "UPDATE EnemyPositions SET Xpos=?, Ypos=? WHERE EnemyID=?", [$nx,$ny,$id]);
            break;
        }
    }
}
echo json_encode(['status'=>'ok']);
