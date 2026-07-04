
<?php
session_start();
include "db.php";
include "check_ban.php";

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['PlayerID'])) {
    die('⛔ Acesso negado. Faça login primeiro.');
}
$playerID = intval($_SESSION['PlayerID']);

function jsonResponse($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

// -------------------------
// Atualizar posição jogador
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move') {
    $newX = isset($_POST['x']) ? intval($_POST['x']) : null;
    $newY = isset($_POST['y']) ? intval($_POST['y']) : null;
    $charID = isset($_POST['charid']) ? intval($_POST['charid']) : 0;

    if ($newX === null || $newY === null || $charID <= 0)
        jsonResponse(['ok' => false, 'error' => 'Parâmetros inválidos']);

    $createSql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='CharacterPositions' AND xtype='U')
    CREATE TABLE CharacterPositions (
        PlayerID INT NOT NULL,
        CharID INT NOT NULL,
        Xpos INT NOT NULL,
        Ypos INT NOT NULL,
        CONSTRAINT PK_CharacterPositions PRIMARY KEY (PlayerID, CharID)
    );";
    @sqlsrv_query($conn, $createSql);

    $check = sqlsrv_query($conn, "SELECT Xpos, Ypos FROM CharacterPositions WHERE PlayerID = ? AND CharID = ?", [$playerID, $charID]);
    if ($check === false) jsonResponse(['ok' => false, 'error' => 'Erro DB (check)']);

    $row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
    if ($row) {
        $update = sqlsrv_query($conn, "UPDATE CharacterPositions SET Xpos=?, Ypos=? WHERE PlayerID=? AND CharID=?", [$newX, $newY, $playerID, $charID]);
        if ($update === false) jsonResponse(['ok' => false, 'error' => 'Erro ao atualizar posição']);
    } else {
        $insert = sqlsrv_query($conn, "INSERT INTO CharacterPositions (PlayerID, CharID, Xpos, Ypos) VALUES (?, ?, ?, ?)", [$playerID, $charID, $newX, $newY]);
        if ($insert === false) jsonResponse(['ok' => false, 'error' => 'Erro ao inserir posição']);
    }

    jsonResponse(['ok' => true, 'x' => $newX, 'y' => $newY]);
}

// -------------------------
// Carregar personagem
// -------------------------
$charID = isset($_GET['charid']) ? intval($_GET['charid']) : 0;
if ($charID <= 0) {
    $q = sqlsrv_query($conn, "SELECT TOP 1 CharID, Name, Class FROM dbo.Characters WHERE PlayerID = ?", [$playerID]);
    if ($q === false) die('Erro ao buscar personagem');
    $r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC);
    if (!$r) die('Nenhum personagem encontrado para este jogador.');
    $charID = intval($r['CharID']);
    $charName = $r['Name'];
    $charClass = $r['Class'];
} else {
    $q = sqlsrv_query($conn, "SELECT CharID, Name, Class FROM dbo.Characters WHERE PlayerID = ? AND CharID = ?", [$playerID, $charID]);
    if ($q === false) die('Erro ao buscar personagem');
    $r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC);
    if (!$r) die('Personagem não encontrado.');
    $charName = $r['Name'];
    $charClass = $r['Class'];
}

// -------------------------
// Inimigos (carregamento)
// -------------------------
$enemyRows = [];
$enemyQ = sqlsrv_query($conn, "SELECT EnemyID, Name, Xpos, Ypos, CharSVG FROM dbo.Enemies");
if ($enemyQ !== false) {
    while ($er = sqlsrv_fetch_array($enemyQ, SQLSRV_FETCH_ASSOC)) {
        $enemyRows[] = [
            'id' => intval($er['EnemyID']),
            'name' => $er['Name'],
            'x' => intval($er['Xpos']),
            'y' => intval($er['Ypos']),
            'svg' => $er['CharSVG'] ?: "<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='#cc0000'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='#666'/></svg>"
        ];
    }
}


// =====================================
// Inimigos
// =====================================
$enemies = [];



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


// Pega SVG personalizado (ou usa padrão)
$svgQ = sqlsrv_query($conn, "SELECT CharSVG FROM dbo.Characters WHERE CharID = ?", [$charID]);
$svgRow = sqlsrv_fetch_array($svgQ, SQLSRV_FETCH_ASSOC);
$charSVG = $svgRow && !empty($svgRow['CharSVG'])
    ? $svgRow['CharSVG']
    : "<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='#ffcc00'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='#007acc'/></svg>";


// -------------------------
// Posição inicial
// -------------------------
$posQ = sqlsrv_query($conn, "SELECT Xpos, Ypos FROM CharacterPositions WHERE PlayerID = ? AND CharID = ?", [$playerID, $charID]);
$pos = sqlsrv_fetch_array($posQ, SQLSRV_FETCH_ASSOC);
if ($pos) {
    $startX = intval($pos['Xpos']);
    $startY = intval($pos['Ypos']);
} else {
    $startX = 1; $startY = 1;
    @sqlsrv_query($conn, "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='CharacterPositions' AND xtype='U') CREATE TABLE CharacterPositions (PlayerID INT NOT NULL, CharID INT NOT NULL, Xpos INT NOT NULL, Ypos INT NOT NULL, CONSTRAINT PK_CharacterPositions PRIMARY KEY (PlayerID, CharID));");
    sqlsrv_query($conn, "INSERT INTO CharacterPositions (PlayerID, CharID, Xpos, Ypos) VALUES (?, ?, ?, ?)", [$playerID, $charID, $startX, $startY]);
}

// -------------------------
// Mapa fixo (simples)
// -------------------------
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
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mapa - <?php echo htmlspecialchars($charName); ?></title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#111;color:#eee;display:flex;gap:20px;padding:20px}
.map-wrap{position:relative;width:calc(40px * <?php echo $cols; ?>);}
.grid{display:grid;grid-template-columns:repeat(<?php echo $cols; ?>,40px);grid-auto-rows:40px;}
.tile{width:40px;height:40px;box-sizing:border-box;border:1px solid rgba(0,0,0,0.15);cursor:pointer;}
.tile.floor{background:#cfcfcf}
.tile.wall{background:#333;cursor:not-allowed;}
.tile.tree{background:linear-gradient(#2b6,#163);cursor:not-allowed;}
.char,.enemy{position:absolute;width:36px;height:36px;transform:translate(-50%,-50%);pointer-events:none}
.hud{padding:8px;background:rgba(255,255,255,0.03);border-radius:8px}
button{padding:6px 10px;margin:2px}
</style>
</head>
<body>
<div class="left">
<div class="hud"><strong><?php echo htmlspecialchars($charName); ?></strong> — Classe: <?php echo htmlspecialchars($charClass); ?><br>
Posição: <span id="posText"><?php echo $startX . ',' . $startY; ?></span></div>

<div class="map-wrap" id="mapWrap">
    <div class="grid" id="grid">
        <?php
        for ($r=0;$r<$rows;$r++){
            for ($c=0;$c<$cols;$c++){
                $ch=$map[$r][$c];
                $cls='tile '.($ch=='#'?'wall':($ch=='T'?'tree':'floor'));
                echo "<div class='$cls' data-x='$c' data-y='$r'></div>";
            }
        }
        ?>
    </div>

    <!-- personagem -->
    <img id="char" class="char"
        src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='%23ffcc00'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='%23007acc'/></svg>">
</div>


<img id="char" class="char"
     src="data:image/svg+xml;utf8,<?php echo rawurlencode($charSVG); ?>">





<div class="hud">
    Use as setas ou clique no mapa para mover.<br>
    <button onclick="moveBy(-1,0)">←</button>
    <button onclick="moveBy(1,0)">→</button>
    <button onclick="moveBy(0,-1)">↑</button>
    <button onclick="moveBy(0,1)">↓</button>
</div>
</div>

<div class="right">
<div class="hud"><strong>Informações</strong><div id="log" style="margin-top:6px;max-width:320px"></div></div>
</div>

<script>
const map = <?php echo json_encode($map); ?>;
const rows = <?php echo $rows; ?>;
const cols = <?php echo $cols; ?>;
let playerX = <?php echo $startX; ?>;
let playerY = <?php echo $startY; ?>;
const charEl = document.getElementById('char');
const posText = document.getElementById('posText');
const log = document.getElementById('log');
const charID = <?php echo $charID; ?>;
const mapWrap = document.getElementById('mapWrap');

function tileAt(x,y){if(y<0||y>=rows||x<0||x>=cols)return'#';return map[y].charAt(x);}
function updateCharDOM(){const s=40;charEl.style.left=(playerX*s+s/2)+'px';charEl.style.top=(playerY*s+s/2)+'px';posText.textContent=playerX+','+playerY;}
updateCharDOM();

function tryMove(nx,ny){const t=tileAt(nx,ny);if(t=='#'||t=='T'){logMsg('Colisão: obstáculo!');return false;}return true;}
function logMsg(t){log.innerText=t;setTimeout(()=>{if(log.innerText===t)log.innerText='';},2000);}
function sendPosition(x,y){const d=new FormData();d.append('action','move');d.append('x',x);d.append('y',y);d.append('charid',charID);fetch(window.location.href,{method:'POST',body:d});}
function moveTo(nx,ny){if(!tryMove(nx,ny))return;playerX=nx;playerY=ny;updateCharDOM();sendPosition(nx,ny);}
function moveBy(dx,dy){moveTo(playerX+dx,playerY+dy);}
window.addEventListener('keydown',e=>{if(e.key==='ArrowLeft')moveBy(-1,0);if(e.key==='ArrowRight')moveBy(1,0);if(e.key==='ArrowUp')moveBy(0,-1);if(e.key==='ArrowDown')moveBy(0,1);});

// 🖱️ Movimento por clique do mouse
document.getElementById('grid').addEventListener('click', e=>{
  const tile = e.target.closest('.tile');
  if (!tile) return;
  const tx = parseInt(tile.dataset.x);
  const ty = parseInt(tile.dataset.y);
  if (tryMove(tx, ty)) moveTo(tx, ty);
});

// =======================
// IA de inimigos + render
// =======================
let enemies = [];
function renderEnemies(){
    document.querySelectorAll('.enemy').forEach(e=>e.remove());
    enemies.forEach(en=>{
        const img=document.createElement('img');
        img.className='enemy';
        const svg = en.svg || "<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='%23cc0000'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='%23666'/></svg>";
        img.src = "data:image/svg+xml;utf8," + encodeURIComponent(svg);
        const s=40;
        img.style.left=(en.x*s+s/2)+'px';
        img.style.top=(en.y*s+s/2)+'px';
        mapWrap.appendChild(img);
    });
}

function renderEnemies() {
    document.querySelectorAll('.enemy').forEach(e => e.remove());
    enemies.forEach(en => {
        const img = document.createElement('img');
        img.className = 'enemy';
        const svg = en.svg || "<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='%23cc0000'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='%23666'/></svg>";
        img.src = "data:image/svg+xml;utf8," + encodeURIComponent(svg);
        const s = 40;
        img.style.left = (en.x * s + s / 2) + 'px';
        img.style.top = (en.y * s + s / 2) + 'px';
        mapWrap.appendChild(img);
    });
}


updateCharDOM();
renderEnemies();


function moveEnemiesRandomly() {
  enemies.forEach(en => {
    const dirs = [[1,0],[-1,0],[0,1],[0,-1]];
    const [dx,dy] = dirs[Math.floor(Math.random()*dirs.length)];
    const nx = en.x + dx, ny = en.y + dy;
    if (tileAt(nx,ny) === '.' && !(nx === playerX && ny === playerY)) {
      en.x = nx; en.y = ny;
    }
  });
  renderEnemies();
}

// Atualiza a IA localmente a cada 4 segundos
setInterval(moveEnemiesRandomly, 4000);


function checkEnemyCollision() {
  for (const en of enemies) {
    if (en.x === playerX && en.y === playerY) {
      logMsg("⚔️ Batalha iniciada com " + en.name + "!");
      iniciarBatalha(en);
      return true;
    }
  }
  return false;
}

function iniciarBatalha(enemy) {
  // Aqui você pode substituir por redirecionamento ou popup de combate
  console.log("Iniciando batalha contra:", enemy.name);
  
  // Exemplo simples: mudar a cor do inimigo e mostrar status
  const battleBox = document.createElement('div');
  battleBox.style.position = 'fixed';
  battleBox.style.left = '50%';
  battleBox.style.top = '50%';
  battleBox.style.transform = 'translate(-50%, -50%)';
  battleBox.style.padding = '20px';
  battleBox.style.background = '#222';
  battleBox.style.color = '#fff';
  battleBox.style.border = '2px solid #f00';
  battleBox.style.borderRadius = '10px';
  battleBox.style.textAlign = 'center';
  battleBox.innerHTML = `
    <h3>⚔️ Batalha!</h3>
    <p>Você encontrou <strong>${enemy.name}</strong></p>
    <button id="closeBattle">Fechar</button>
  `;
  document.body.appendChild(battleBox);
  document.getElementById('closeBattle').onclick = () => battleBox.remove();
}



function moveTo(nx, ny) {
  if (!tryMove(nx, ny)) return;
  playerX = nx;
  playerY = ny;
  updateCharDOM();
  sendPosition(nx, ny);
  checkEnemyCollision(); // 🧠 Verifica se encostou em algum inimigo
}



document.querySelectorAll('.enemy').forEach(e => {
  if (e.style.left === ((enemy.x * 40) + 20) + 'px' &&
      e.style.top === ((enemy.y * 40) + 20) + 'px') {
    e.style.filter = 'drop-shadow(0 0 6px red)';
  }
});

const enemies = <?php echo json_encode($enemies); ?>;
const enemies = <?php echo json_encode($enemyRows, JSON_UNESCAPED_UNICODE); ?>;




// Atualiza inimigos a cada 5s
function atualizarInimigos(){
    fetch('sync.php')
      .then(r=>r.json())
      .then(data=>{
         enemies = data.enemies || [];
         renderEnemies();
      });
}
setInterval(()=>{fetch('enemy_ai.php').then(()=>atualizarInimigos());},5000);
atualizarInimigos();
</script>
</body>
</html>
