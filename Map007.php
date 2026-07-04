<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['PlayerID'])) {
    die('⛔ Acesso negado. Faça login primeiro.');
}
$playerID = intval($_SESSION['PlayerID']);

// Cria tabela de posições se não existir
$createCharPos = "
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='CharacterPositions' AND xtype='U')
CREATE TABLE CharacterPositions (
    PlayerID INT NOT NULL,
    CharID INT NOT NULL,
    Xpos INT NOT NULL,
    Ypos INT NOT NULL,
    CONSTRAINT PK_CharacterPositions PRIMARY KEY (PlayerID, CharID)
);
";
@sqlsrv_query($conn, $createCharPos);

$createEnemyPos = "
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='EnemyPositions' AND xtype='U')
CREATE TABLE EnemyPositions (
    EnemyID INT PRIMARY KEY,
    Xpos INT NOT NULL,
    Ypos INT NOT NULL
);
";
@sqlsrv_query($conn, $createEnemyPos);

// =====================================
// Pega personagem do jogador
// =====================================
$q = sqlsrv_query($conn, "
    SELECT TOP 1 
        CharID, Name, Class, Level, HP, MaxHP, Mana, MaxMana, Power, Exp
    FROM dbo.Characters
    WHERE PlayerID = ?
", [$playerID]);

$r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC);
if (!$r) {
    $r = [
        'CharID' => 0,
        'Name' => 'Desconhecido',
        'Class' => 'Nenhuma',
        'Level' => 1,
        'HP' => 100,
        'MaxHP' => 100,
        'Mana' => 50,
        'MaxMana' => 50,
        'Power' => 10,
        'Exp' => 0
    ];
}
$charID = $r['CharID'];

// =====================================
// Posição do personagem
// =====================================
$posQ = sqlsrv_query($conn, "SELECT Xpos, Ypos FROM CharacterPositions WHERE PlayerID = ? AND CharID = ?", [$playerID, $charID]);
$pos = sqlsrv_fetch_array($posQ, SQLSRV_FETCH_ASSOC);
if ($pos) {
    $startX = intval($pos['Xpos']);
    $startY = intval($pos['Ypos']);
} else {
    $startX = 1; 
    $startY = 1;
    sqlsrv_query($conn, "INSERT INTO CharacterPositions (PlayerID, CharID, Xpos, Ypos) VALUES (?, ?, ?, ?)", [$playerID, $charID, $startX, $startY]);
}

// =====================================
// Personagens online
// =====================================
$charsOnline = [];
$sqlChars = "
SELECT c.CharID, c.PlayerID, c.Name, c.Class, c.Level, c.Exp, c.HP, c.Mana, p.Xpos, p.Ypos
FROM dbo.Characters c
JOIN CharacterPositions p ON c.PlayerID = p.PlayerID AND c.CharID = p.CharID
WHERE p.Xpos IS NOT NULL AND p.Ypos IS NOT NULL
";
$stmtChars = sqlsrv_query($conn, $sqlChars);
if ($stmtChars !== false) {
    while ($row = sqlsrv_fetch_array($stmtChars, SQLSRV_FETCH_ASSOC)) {
        $charsOnline[] = $row;
    }
}

// =====================================
// Inimigos
// =====================================
$enemies = [];

echo "<pre>"; print_r($enemies); echo "</pre>";


$sqlEnemies = "
SELECT e.EnemyID, e.Name, 
       ISNULL(e.HP, 0) AS HP,
       ISNULL(e.MaxHP, 0) AS MaxHP,
       ISNULL(e.Level, 1) AS Level,
       ISNULL(e.Mana, 0) AS Mana,
       ISNULL(e.MaxMana, 0) AS MaxMana,
       ISNULL(e.Attack, 0) AS Attack,
       ISNULL(e.Defense, 0) AS Defense,
       ISNULL(e.Element, 'Nenhum') AS Element,
       p.Xpos, p.Ypos
FROM dbo.Enemies e
INNER JOIN dbo.EnemyPositions p ON e.EnemyID = p.EnemyID
WHERE p.Xpos IS NOT NULL AND p.Ypos IS NOT NULL
";


$stmtEnemies = sqlsrv_query($conn, $sqlEnemies);
if ($stmtEnemies !== false) {
    while ($row = sqlsrv_fetch_array($stmtEnemies, SQLSRV_FETCH_ASSOC)) {
        $enemies[] = $row;
    }
}

// =====================================
// Mapa base
// =====================================
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
?>

<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Mapa RPG - Multiplayer + Inimigos</title>
<style>
body{font-family:Arial;background:#111;color:#eee;padding:20px}
.map-wrap{position:relative;width:calc(40px * <?php echo $cols; ?>);}
.grid{display:grid;grid-template-columns:repeat(<?php echo $cols; ?>,40px);grid-auto-rows:40px;}
.tile{width:40px;height:40px;box-sizing:border-box;border:1px solid rgba(0,0,0,.15)}
.tile.floor{background:#ccc}.tile.wall{background:#333}.tile.tree{background:linear-gradient(#2b6,#163)}
.char{position:absolute;width:36px;height:36px;transform:translate(-50%,-50%)}
.charIcon{position:absolute;width:30px;height:30px;border-radius:50%;cursor:pointer;opacity:0.9}
.charIcon.enemy{background:#b00;border:2px solid #f44;box-shadow:0 0 8px #f33;}
#hud{margin-bottom:10px;padding:10px;background:#222;border-radius:8px;width:320px;}
</style>
</head>
<body>
<h2>Mapa - <?php echo htmlspecialchars($r['Name']); ?></h2>

<div id="hud">
  <strong><?php echo htmlspecialchars($r['Name']); ?> (<?php echo htmlspecialchars($r['Class']); ?>)</strong><br>
  Level: <span id="hudLevel"><?php echo $r['Level']; ?></span><br>
  HP: <span id="hudHP"><?php echo $r['HP']; ?></span> / <span id="hudMaxHP"><?php echo $r['MaxHP']; ?></span><br>
  Mana: <span id="hudMana"><?php echo $r['Mana']; ?></span> / <span id="hudMaxMana"><?php echo $r['MaxMana']; ?></span><br>
  Power: <span id="hudPower"><?php echo $r['Power']; ?></span><br>
  Exp: <span id="hudExp"><?php echo $r['Exp']; ?></span>
</div>

<div class="map-wrap" id="mapWrap">
  <div class="grid" id="grid">
    <?php
    for ($rI = 0; $rI < $rows; $rI++) {
        for ($c = 0; $c < $cols; $c++) {
            $ch = $map[$rI][$c];
            $cls = 'tile ';
            if ($ch == '#') $cls .= 'wall';
            elseif ($ch == 'T') $cls .= 'tree';
            else $cls .= 'floor';
            echo "<div class='$cls' data-x='$c' data-y='$rI'></div>\n";
        }
    }
    ?>
  </div>
  <img id="char" class="char" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='%23ffcc00'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='%23007acc'/></svg>">
</div>

<script>
const map = <?php echo json_encode($map); ?>;
const rows = <?php echo $rows; ?>;
const cols = <?php echo $cols; ?>;
const allChars = <?php echo json_encode($charsOnline); ?>;
const enemies = <?php echo json_encode($enemies); ?>;
let playerX = <?php echo $startX; ?>;
let playerY = <?php echo $startY; ?>;
const charEl = document.getElementById('char');
const mapWrap = document.getElementById('mapWrap');

function tileAt(x, y){ if(y<0||y>=rows||x<0||x>=cols) return '#'; return map[y].charAt(x); }
function updateCharDOM(){
    const t=40;
    charEl.style.left=(playerX*t+t/2)+'px';
    charEl.style.top=(playerY*t+t/2)+'px';
}
updateCharDOM();

// === Desenha outros personagens ===
allChars.forEach(ch=>{
    if(ch.CharID===<?php echo $charID; ?>)return;
    const el=document.createElement('div');
    el.className='charIcon';
    el.style.left=(ch.Xpos*40+20)+'px';
    el.style.top=(ch.Ypos*40+20)+'px';
    el.style.background='#'+((ch.PlayerID*99999)&0xFFFFFF).toString(16).padStart(6,'0');
    el.title=`${ch.Name} (${ch.Class})\\nLv ${ch.Level}\\nHP:${ch.HP}\\nMana:${ch.Mana}`;
    mapWrap.appendChild(el);
});

// === Desenha inimigos ===
enemies.forEach(en=>{
    const el=document.createElement('div');
    el.className='charIcon enemy';
    el.style.left=(en.Xpos*40+20)+'px';
    el.style.top=(en.Ypos*40+20)+'px';
    el.title=`${en.Name} (Lv ${en.Level})\\nHP: ${en.HP}/${en.MaxHP}\\nElemento: ${en.Element}`;
    mapWrap.appendChild(el);
});

// === Movimento do jogador ===
window.addEventListener('keydown',e=>{
    let nx=playerX, ny=playerY;
    if(e.key==='ArrowLeft')nx--;
    if(e.key==='ArrowRight')nx++;
    if(e.key==='ArrowUp')ny--;
    if(e.key==='ArrowDown')ny++;
    if(tileAt(nx,ny)==='#'||tileAt(nx,ny)==='T')return;
    playerX=nx;playerY=ny;updateCharDOM();
    const data=new FormData();
    data.append('action','move');
    data.append('x',nx);
    data.append('y',ny);
    data.append('charid',<?php echo $charID; ?>);
    fetch(location.href,{method:'POST',body:data});
});
</script>
</body>
</html>
