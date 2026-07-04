<?php
session_start();
include "db.php"; // seu arquivo de conexão SQL Server usando sqlsrv
include "check_ban.php"; // se usar (opcional)

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['PlayerID'])) {
    // Para testes, você pode setar manualmente: $_SESSION['PlayerID'] = 5;
    die('⛔ Acesso negado. Faça login primeiro.');
}
$playerID = intval($_SESSION['PlayerID']);

// Helper para responder AJAX
function jsonResponse($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

// -------------------------------------------------
// Endpoint AJAX: atualiza posição
// -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move') {
    // proteção básica
    $newX = isset($_POST['x']) ? intval($_POST['x']) : null;
    $newY = isset($_POST['y']) ? intval($_POST['y']) : null;
    $charID = isset($_POST['charid']) ? intval($_POST['charid']) : 0;

    if ($newX === null || $newY === null || $charID <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Parâmetros inválidos']);
    }

    // Cria tabela de posições se não existir
    $createSql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='CharacterPositions' AND xtype='U')
    CREATE TABLE CharacterPositions (
        PlayerID INT NOT NULL,
        CharID INT NOT NULL,
        Xpos INT NOT NULL,
        Ypos INT NOT NULL,
        CONSTRAINT PK_CharacterPositions PRIMARY KEY (PlayerID, CharID)
    );";
    @sqlsrv_query($conn, $createSql);

    // Upsert (merge-like)
    $check = sqlsrv_query($conn, "SELECT Xpos, Ypos FROM CharacterPositions WHERE PlayerID = ? AND CharID = ?", [$playerID, $charID]);
    if ($check === false) jsonResponse(['ok' => false, 'error' => 'Erro DB (check)']);

    $row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
    if ($row) {
        $update = sqlsrv_query($conn, "UPDATE CharacterPositions SET Xpos = ?, Ypos = ? WHERE PlayerID = ? AND CharID = ?", [$newX, $newY, $playerID, $charID]);
        if ($update === false) jsonResponse(['ok' => false, 'error' => 'Erro ao atualizar posição']);
    } else {
        $insert = sqlsrv_query($conn, "INSERT INTO CharacterPositions (PlayerID, CharID, Xpos, Ypos) VALUES (?, ?, ?, ?)", [$playerID, $charID, $newX, $newY]);
        if ($insert === false) jsonResponse(['ok' => false, 'error' => 'Erro ao inserir posição']);
    }

    jsonResponse(['ok' => true, 'x' => $newX, 'y' => $newY]);
}

// -------------------------------------------------
// Página principal: carrega personagem e posição
// -------------------------------------------------
$charID = isset($_GET['charid']) ? intval($_GET['charid']) : 0;
if ($charID <= 0) {
    // tenta pegar primeiro personagem do player
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

// Busca posição atual (ou insere padrão 1,1)
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

// -------------------------------------------------
// Mapa: matriz fixa (você pode popular desta forma ou carregar do DB)
// '.' piso livre, '#' parede/obstáculo
// -------------------------------------------------
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
    body{font-family: Arial, Helvetica, sans-serif; background:#111; color:#eee; display:flex; gap:20px; padding:20px}
    .left{max-width:640px}
    .map-wrap{position:relative; width: calc(40px * <?php echo $cols; ?>);}
    .grid{display:grid; grid-template-columns: repeat(<?php echo $cols; ?>, 40px); grid-auto-rows: 40px;}
    .tile{width:40px; height:40px; box-sizing:border-box; border:1px solid rgba(0,0,0,0.15);}
    .tile.floor{background:#cfcfcf}
    .tile.wall{background:#333}
    .tile.tree{background:linear-gradient(#2b6,#163);}
    .char{position:absolute; width:36px; height:36px; transform:translate(-50%,-50%); pointer-events:none}
    .hud{padding:8px; background:rgba(255,255,255,0.03); border-radius:8px}
    .controls{margin-top:8px}
    button{padding:6px 10px; margin:2px}
</style>
</head>
<body>
<div class="left">
    <div class="hud">
        <strong><?php echo htmlspecialchars($charName); ?></strong> — Classe: <?php echo htmlspecialchars($charClass); ?><br>
        Posição: <span id="posText"><?php echo $startX . ',' . $startY; ?></span>
    </div>

    <div class="map-wrap" id="mapWrap">
        <div class="grid" id="grid">
            <?php
            for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $cols; $c++) {
                    $ch = $map[$r][$c];
                    $cls = 'tile ';
                    if ($ch === '#') $cls .= 'wall';
                    elseif ($ch === 'T') $cls .= 'tree';
                    else $cls .= 'floor';
                    echo "<div class=\"$cls\" data-x=\"$c\" data-y=\"$r\"></div>\n";
                }
            }
            ?>
        </div>

        <!-- personagem (usar imagem ou emoji) -->
        <img id="char" class="char" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><circle cx='18' cy='12' r='8' fill='%23ffcc00'/><rect x='8' y='20' width='20' height='12' rx='3' ry='3' fill='%23007acc'/></svg>" alt="char">

    </div>

    <div class="controls hud">
        Use as setas do teclado para mover. <br>
        <div style="margin-top:6px">
            <button onclick="moveBy(-1,0)">←</button>
            <button onclick="moveBy(1,0)">→</button>
            <button onclick="moveBy(0,-1)">↑</button>
            <button onclick="moveBy(0,1)">↓</button>
        </div>
        <div style="margin-top:6px">Colisão: paredes (#) e árvores (T) são obstáculos.</div>
    </div>
</div>

<div class="right">
    <div class="hud">
        <strong>Informações</strong>
        <div id="log" style="margin-top:6px; max-width:320px"></div>
    </div>
</div>

<script>
const map = <?php echo json_encode($map); ?>; // array de strings
const rows = <?php echo $rows; ?>;
const cols = <?php echo $cols; ?>;
let playerX = <?php echo $startX; ?>;
let playerY = <?php echo $startY; ?>;
const charEl = document.getElementById('char');
const grid = document.getElementById('grid');
const posText = document.getElementById('posText');
const log = document.getElementById('log');
const charID = <?php echo $charID; ?>;

function tileAt(x,y){
    if (y < 0 || y >= rows || x < 0 || x >= cols) return '#';
    return map[y].charAt(x);
}

function updateCharDOM(){
    // calcula pixel position: center of tile
    const tileSize = 40;
    const left = playerX * tileSize + tileSize/2;
    const top = playerY * tileSize + tileSize/2;
    charEl.style.left = left + 'px';
    charEl.style.top = top + 'px';
    posText.textContent = playerX + ',' + playerY;
}

// inicia posição
updateCharDOM();

function logMsg(t){
    log.innerText = t;
    setTimeout(()=>{ if (log.innerText === t) log.innerText = '' }, 2000);
}

function tryMove(nx, ny){
    const t = tileAt(nx, ny);
    if (t === '#' || t === 'T') { logMsg('Colisão: obstáculo!'); return false; }
    return true;
}

function sendPosition(x,y){
    const data = new FormData();
    data.append('action','move');
    data.append('x', x);
    data.append('y', y);
    data.append('charid', charID);

    fetch(window.location.href, { method:'POST', body: data })
    .then(r=>r.json())
    .then(j=>{
        if (j.ok){
            // sucesso
        } else {
            logMsg('Erro ao salvar: ' + (j.error || 'unknown'));
        }
    }).catch(e=>{ logMsg('Erro fetch: ' + e.message); });
}

function moveTo(nx, ny){
    if (!tryMove(nx, ny)) return;
    playerX = nx; playerY = ny;
    updateCharDOM();
    sendPosition(nx, ny);
}

function moveBy(dx, dy){
    const nx = playerX + dx;
    const ny = playerY + dy;
    moveTo(nx, ny);
}

window.addEventListener('keydown', (e)=>{
    if (e.key === 'ArrowLeft') { moveBy(-1,0); e.preventDefault(); }
    if (e.key === 'ArrowRight') { moveBy(1,0); e.preventDefault(); }
    if (e.key === 'ArrowUp') { moveBy(0,-1); e.preventDefault(); }
    if (e.key === 'ArrowDown') { moveBy(0,1); e.preventDefault(); }
});

// Clique na grade para teleporte (somente para DEBUG)
grid.addEventListener('click', (ev)=>{
    const rect = grid.getBoundingClientRect();
    const x = Math.floor((ev.clientX - rect.left) / 40);
    const y = Math.floor((ev.clientY - rect.top) / 40);
    if (x >=0 && x < cols && y >=0 && y < rows) moveTo(x,y);
});

</script>
</body>
</html>
