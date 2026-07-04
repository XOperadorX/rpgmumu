<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['PlayerID'])) {
    die('⛔ Acesso negado. Faça login primeiro.');
}
$playerID = intval($_SESSION['PlayerID']);

// ====== Criação de tabelas ======
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

// ====== AJAX endpoint para atualização de posição ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='move') {
    $newX = isset($_POST['x']) ? intval($_POST['x']) : null;
    $newY = isset($_POST['y']) ? intval($_POST['y']) : null;
    $charID = isset($_POST['charid']) ? intval($_POST['charid']) : 0;

    if ($newX===null || $newY===null || $charID<=0) { echo json_encode(['ok'=>false,'error'=>'Parâmetros inválidos']); exit; }

    $check = sqlsrv_query($conn, "SELECT Xpos,Ypos FROM CharacterPositions WHERE PlayerID=? AND CharID=?", [$playerID,$charID]);
    $row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
    if($row){
        sqlsrv_query($conn, "UPDATE CharacterPositions SET Xpos=?,Ypos=? WHERE PlayerID=? AND CharID=?", [$newX,$newY,$playerID,$charID]);
    } else {
        sqlsrv_query($conn, "INSERT INTO CharacterPositions (PlayerID,CharID,Xpos,Ypos) VALUES (?,?,?,?)", [$playerID,$charID,$newX,$newY]);
    }

    echo json_encode(['ok'=>true,'x'=>$newX,'y'=>$newY]);
    exit;
}

// ====== Carregar personagem ======
$q = sqlsrv_query($conn, "SELECT TOP 1 CharID, Name, Class, Level, HP, MaxHP, Mana, MaxMana, Power, Exp FROM dbo.Characters WHERE PlayerID=?", [$playerID]);
$r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC);
if (!$r){
    $r=['CharID'=>0,'Name'=>'Desconhecido','Class'=>'Nenhuma','Level'=>1,'HP'=>100,'MaxHP'=>100,'Mana'=>50,'MaxMana'=>50,'Power'=>10,'Exp'=>0];
}
$charID=intval($r['CharID']);

// ====== Posição do personagem ======
$posQ = sqlsrv_query($conn, "SELECT Xpos,Ypos FROM CharacterPositions WHERE PlayerID=? AND CharID=?", [$playerID,$charID]);
$pos = sqlsrv_fetch_array($posQ, SQLSRV_FETCH_ASSOC);
if($pos){ $startX=intval($pos['Xpos']); $startY=intval($pos['Ypos']); }
else{ $startX=1; $startY=1; sqlsrv_query($conn, "INSERT INTO CharacterPositions (PlayerID,CharID,Xpos,Ypos) VALUES (?,?,?,?)", [$playerID,$charID,$startX,$startY]); }

// ====== Personagens online ======
$charsOnline=[];
$sqlChars = "SELECT c.CharID,c.PlayerID,c.Name,c.Class,c.Level,c.Exp,c.HP,c.Mana,p.Xpos,p.Ypos
             FROM dbo.Characters c
             JOIN CharacterPositions p ON c.PlayerID=p.PlayerID AND c.CharID=p.CharID
             WHERE p.Xpos IS NOT NULL AND p.Ypos IS NOT NULL";
$stmtChars = sqlsrv_query($conn,$sqlChars);
while($row=sqlsrv_fetch_array($stmtChars,SQLSRV_FETCH_ASSOC)) $charsOnline[]=$row;

// ====== Inimigos ======
$enemies=[];
$sqlEnemies = "SELECT e.EnemyID,e.Name, ISNULL(e.HP,0) AS HP, ISNULL(e.MaxHP,0) AS MaxHP, ISNULL(e.Level,1) AS Level,
ISNULL(e.Mana,0) AS Mana, ISNULL(e.MaxMana,0) AS MaxMana, ISNULL(e.Attack,0) AS Attack,
ISNULL(e.Defense,0) AS Defense, ISNULL(e.Element,'Nenhum') AS Element,
p.Xpos,p.Ypos FROM dbo.Enemies e INNER JOIN dbo.EnemyPositions p ON e.EnemyID=p.EnemyID
WHERE p.Xpos IS NOT NULL AND p.Ypos IS NOT NULL";
$stmtEnemies = sqlsrv_query($conn,$sqlEnemies);
while($row=sqlsrv_fetch_array($stmtEnemies,SQLSRV_FETCH_ASSOC)) $enemies[]=$row;

if(empty($enemies)){
    $enemies[]=['EnemyID'=>1,'Xpos'=>5,'Ypos'=>5,'Name'=>'Teste Goblin','Level'=>1,'HP'=>50,'MaxHP'=>50,'Element'=>'Nenhum'];
    $enemies[]=['EnemyID'=>2,'Xpos'=>10,'Ypos'=>8,'Name'=>'Teste Orc','Level'=>2,'HP'=>80,'MaxHP'=>80,'Element'=>'Nenhum'];
}

// ====== Mapa ======
$map=[
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
$rows=count($map);
$cols=strlen($map[0]);
?>

<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Mapa RPG - <?php echo htmlspecialchars($r['Name']); ?></title>
<style>
body{font-family:Arial;background:#111;color:#eee;padding:20px;display:flex;gap:20px}
.left{max-width:640px}
.map-wrap{position:relative;width:calc(40px*<?php echo $cols; ?>);}
.grid{display:grid;grid-template-columns:repeat(<?php echo $cols; ?>,40px);grid-auto-rows:40px;}
.tile{width:40px;height:40px;box-sizing:border-box;border:1px solid rgba(0,0,0,.15);}
.tile.floor{background:#ccc}.tile.wall{background:#333}.tile.tree{background:linear-gradient(#2b6,#163);}
.char{position:absolute;width:36px;height:36px;transform:translate(-50%,-50%);pointer-events:none}
.charIcon{position:absolute;width:30px;height:30px;border-radius:50%;cursor:pointer;opacity:0.9}
.charIcon.enemy{background:#b00;border:2px solid #f44;box-shadow:0 0 8px #f33;}
.charIcon.other{border:2px solid #0f0;box-shadow:0 0 6px #0f0;}
.hud{padding:10px;background:#222;border-radius:8px;width:320px;}
button{padding:6px 10px;margin:2px}
.controls{margin-top:10px}
</style>
</head>
<body>
<div class="left">
<div class="hud">
<strong><?php echo htmlspecialchars($r['Name']); ?> (<?php echo htmlspecialchars($r['Class']); ?>)</strong><br>
Level: <?php echo $r['Level']; ?><br>
HP: <?php echo $r['HP'].'/'.$r['MaxHP']; ?><br>
Mana: <?php echo $r['Mana'].'/'.$r['MaxMana']; ?><br>
Power: <?php echo $r['Power']; ?><br>
Exp: <?php echo $r['Exp']; ?><br>
Posição: <span id="posText"><?php echo $startX.','.$startY; ?></span>
</div>

<div class="map-wrap" id="mapWrap">
<div class="grid" id="grid">
<?php
for($y=0;$y<$rows;$y++){
    for($x=0;$x<$cols;$x++){
        $ch=$map[$y][$x];
        $cls='tile '.($ch=='#'?'wall':($ch=='T'?'tree':'floor'));
        echo "<div class='$cls' data-x='$x' data-y='$y'></div>";
    }
}
?>
</div>
<img id="char" class="char" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='%23ffcc00'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='%23007acc'/></svg>">
<div class="controls hud">
Use setas ou botões:<br>
<button onclick="moveBy(-1,0)">←</button>
<button onclick="moveBy(1,0)">→</button>
<button onclick="moveBy(0,-1)">↑</button>
<button onclick="moveBy(0,1)">↓</button>
</div>
</div>
</div>

<script>
const map=<?php echo json_encode($map); ?>;
const rows=<?php echo $rows; ?>;
const cols=<?php echo $cols; ?>;
let playerX=<?php echo $startX; ?>;
let playerY=<?php echo $startY; ?>;
const charID=<?php echo $charID; ?>;
const charEl=document.getElementById('char');
const posText=document.getElementById('posText');
const mapWrap=document.getElementById('mapWrap');
const tileSize=40;
let enemies=<?php echo json_encode($enemies); ?>;
let allChars=<?php echo json_encode($charsOnline); ?>;

// ======= Funções =======
function tileAt(x,y){ if(y<0||y>=rows||x<0||x>=cols)return '#'; return map[y].charAt(x); }
function atualizarCharDOM(){ charEl.style.left=(playerX*tileSize+tileSize/2)+'px'; charEl.style.top=(playerY*tileSize+tileSize/2)+'px'; posText.textContent=playerX+','+playerY; }
function tryMove(nx,ny){ const t=tileAt(nx,ny); return !(t==='#'||t==='T'); }
function sendPosition(x,y){ const data=new FormData(); data.append('action','move'); data.append('x',x); data.append('y',y); data.append('charid',charID); fetch(window.location.href,{method:'POST',body:data}); }
function moveTo(nx,ny){ if(!tryMove(nx,ny)) return; const enemy=getEnemyAt(nx,ny); if(enemy){ iniciarBatalha(enemy); return; } playerX=nx; playerY=ny; atualizarCharDOM(); sendPosition(nx,ny);}
function moveBy(dx,dy){ moveTo(playerX+dx,playerY+dy);}
function getEnemyAt(x,y){ return enemies.find(e=>e.Xpos===x && e.Ypos===y); }
function iniciarBatalha(enemy){ alert('Batalha iniciada com '+enemy.Name+'!'); }

// ======= Movimentação inimigos =======
function moverInimigos(){
    enemies.forEach(e=>{
        const dirs=[[0,1],[0,-1],[1,0],[-1,0]];
        const d=dirs[Math.floor(Math.random()*dirs.length)];
        const nx=e.Xpos+d[0], ny=e.Ypos+d[1];
        if(tryMove(nx,ny) && !(nx===playerX && ny===playerY)) { e.Xpos=nx; e.Ypos=ny; }
    });
    desenharInimigos();
}
function desenharInimigos(){
    document.querySelectorAll('.enemy').forEach(el=>el.remove());
    enemies.forEach(e=>{
        const el=document.createElement('div');
        el.className='charIcon enemy';
        el.style.left=(e.Xpos*tileSize+tileSize/2)+'px';
        el.style.top=(e.Ypos*tileSize+tileSize/2)+'px';
        el.title=e.Name;
        mapWrap.appendChild(el);
    });
}

// ======= Inicialização =======
atualizarCharDOM();
desenharInimigos();
window.addEventListener('keydown',e=>{
    if(e.key==='ArrowLeft'){moveBy(-1,0);e.preventDefault();}
    if(e.key==='ArrowRight'){moveBy(1,0);e.preventDefault();}
    if(e.key==='ArrowUp'){moveBy(0,-1);e.preventDefault();}
    if(e.key==='ArrowDown'){moveBy(0,1);e.preventDefault();}
});
setInterval(moverInimigos,1000);
</script>
</body>
</html>
