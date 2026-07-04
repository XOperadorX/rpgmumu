<nav>
<a href="admin_dashboard.php">⬅ Voltar ao Painel</a>
<a href="logout.php">🚪 Sair</a>
</nav>

<?php
session_start();
include "../db.php";

// Verifica login e role
if (!isset($_SESSION['PlayerID'])) die("⛔ Faça login.");
$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID = ?", [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if ($row['Role'] !== 'admin') die("⛔ Acesso negado. Apenas admins.");

// Atualização via AJAX
if(isset($_POST['action']) && $_POST['action'] === 'update'){
    $sql = "UPDATE Characters SET 
                Name=?, Class=?, Level=?, Exp=?, 
                HP=?, MaxHP=?, Mana=?, MaxMana=?, 
                Power=?, MaxPower=?
            WHERE CharID=?";
    $params = [
        $_POST['Name'], $_POST['Class'], $_POST['Level'], $_POST['Exp'],
        $_POST['HP'], $_POST['MaxHP'], $_POST['Mana'], $_POST['MaxMana'],
        $_POST['Power'], $_POST['MaxPower'], $_POST['CharID']
    ];
    sqlsrv_query($conn, $sql, $params);
    echo "success";
    exit;
}

// Função para carregar personagens
function renderCharacters($conn, $playerFilter = null, $nameFilter = null, $order = "Level DESC"){
    $sql = "SELECT * FROM Characters WHERE 1=1";
    $params = [];

    if($playerFilter && $playerFilter !== "all"){
        $sql .= " AND PlayerID = ?";
        $params[] = $playerFilter;
    }
    if($nameFilter){
        $sql .= " AND Name LIKE ?";
        $params[] = "%$nameFilter%";
    }

    // segurança no ORDER BY (somente colunas permitidas)
    $allowedOrders = [
        "Level ASC","Level DESC",
        "Exp ASC","Exp DESC",
        "HP ASC","HP DESC",
        "Mana ASC","Mana DESC",
        "Power ASC","Power DESC"
    ];
    if(!in_array($order,$allowedOrders)) $order = "Level DESC";

    $sql .= " ORDER BY $order";

    $result = sqlsrv_query($conn, $sql, $params);

    if(!$result){
        echo "<p style='color:red'>Erro ao carregar personagens.</p>";
        return;
    }

    while($char = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)){
        $hpPercent   = ($char['MaxHP']   > 0) ? ($char['HP']   / $char['MaxHP'])   * 100 : 0;
        $manaPercent = ($char['MaxMana'] > 0) ? ($char['Mana'] / $char['MaxMana']) * 100 : 0;
        $powPercent  = ($char['MaxPower']> 0) ? ($char['Power']/ $char['MaxPower'])* 100 : 0;

        echo "<div class='char-card' data-id='{$char['CharID']}'>
                <form class='char-form'>
                    <input type='hidden' name='CharID' value='{$char['CharID']}'>
                    Nome: <input type='text' name='Name' value='{$char['Name']}'>
                    Classe: <input type='text' name='Class' value='{$char['Class']}'>
                    Level: <input type='number' name='Level' value='{$char['Level']}'>
                    Exp: <input type='number' name='Exp' value='{$char['Exp']}'>
                    HP: <input type='number' name='HP' value='{$char['HP']}'>
                    MaxHP: <input type='number' name='MaxHP' value='{$char['MaxHP']}'>
                    Mana: <input type='number' name='Mana' value='{$char['Mana']}'>
                    MaxMana: <input type='number' name='MaxMana' value='{$char['MaxMana']}'>
                    Power: <input type='number' name='Power' value='{$char['Power']}'>
                    MaxPower: <input type='number' name='MaxPower' value='{$char['MaxPower']}'>
                    
                    <div class='bar'><div class='hp-bar' style='width:{$hpPercent}%;'>HP: {$char['HP']}/{$char['MaxHP']}</div></div>
                    <div class='bar'><div class='mana-bar' style='width:{$manaPercent}%;'>Mana: {$char['Mana']}/{$char['MaxMana']}</div></div>
                    <div class='bar'><div class='power-bar' style='width:{$powPercent}%;'>Power: {$char['Power']}/{$char['MaxPower']}</div></div>

                    <button type='submit'>Atualizar</button>
                </form>
              </div>";
    }
}

// filtros atuais
$playerFilter = isset($_GET['player']) ? $_GET['player'] : "all";
$nameFilter   = isset($_GET['name']) ? trim($_GET['name']) : "";
$order        = isset($_GET['order']) ? $_GET['order'] : "Level DESC";

// pegar lista de players para o select
$players = sqlsrv_query($conn, "SELECT PlayerID, Username FROM Players ORDER BY Username ASC");
?>

<div class="container">
    <form method="get" id="filter-form">
        <label>Filtrar por Player:</label>
        <select name="player" onchange="document.getElementById('filter-form').submit()">
            <option value="all" <?= $playerFilter==="all"?"selected":"" ?>>Todos</option>
            <?php while($p = sqlsrv_fetch_array($players, SQLSRV_FETCH_ASSOC)){ ?>
                <option value="<?= $p['PlayerID'] ?>" <?= $playerFilter==$p['PlayerID']?"selected":"" ?>>
                    <?= htmlspecialchars($p['Username']) ?> (ID: <?= $p['PlayerID'] ?>)
                </option>
            <?php } ?>
        </select>

        <label>Buscar por Nome:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($nameFilter) ?>" placeholder="Ex: Arqueiro" onkeyup="delaySearch()">

        <label>Ordenar por:</label>
        <select name="order" onchange="document.getElementById('filter-form').submit()">
            <option value="Level DESC" <?= $order==="Level DESC"?"selected":"" ?>>Level ↓</option>
            <option value="Level ASC"  <?= $order==="Level ASC"?"selected":"" ?>>Level ↑</option>
            <option value="Exp DESC"   <?= $order==="Exp DESC"?"selected":"" ?>>Exp ↓</option>
            <option value="Exp ASC"    <?= $order==="Exp ASC"?"selected":"" ?>>Exp ↑</option>
            <option value="HP DESC"    <?= $order==="HP DESC"?"selected":"" ?>>HP ↓</option>
            <option value="HP ASC"     <?= $order==="HP ASC"?"selected":"" ?>>HP ↑</option>
            <option value="Mana DESC"  <?= $order==="Mana DESC"?"selected":"" ?>>Mana ↓</option>
            <option value="Mana ASC"   <?= $order==="Mana ASC"?"selected":"" ?>>Mana ↑</option>
            <option value="Power DESC" <?= $order==="Power DESC"?"selected":"" ?>>Power ↓</option>
            <option value="Power ASC"  <?= $order==="Power ASC"?"selected":"" ?>>Power ↑</option>
        </select>
    </form>
</div>

<div id="characters-list">
    <?php renderCharacters($conn, $playerFilter, $nameFilter, $order); ?>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    attachForms();
});

// atraso para não pesquisar a cada tecla
let searchTimeout;
function delaySearch(){
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(()=>{
        document.getElementById('filter-form').submit();
    }, 500);
}

function attachForms(){
    document.querySelectorAll('.char-form').forEach(form => {
        form.addEventListener('submit', e => {
            e.preventDefault();
            let data = new FormData(form);
            data.append('action','update');
            fetch('admin_Characters_Edit.php?player=<?= $playerFilter ?>&name=<?= urlencode($nameFilter) ?>&order=<?= urlencode($order) ?>',{method:'POST',body:data})
                .then(res=>res.text())
                .then(resp=>{
                    if(resp==='success'){
                        fetch('admin_Characters_Edit.php?player=<?= $playerFilter ?>&name=<?= urlencode($nameFilter) ?>&order=<?= urlencode($order) ?>')
                            .then(res=>res.text())
                            .then(html=>{
                                let parser = new DOMParser();
                                let doc = parser.parseFromString(html,'text/html');
                                document.getElementById('characters-list').innerHTML =
                                    doc.getElementById('characters-list').innerHTML;
                                attachForms();
                            });
                    }
                });
        });
    });
}
</script>

<style>
body { font-family: Arial,sans-serif; background:#f4f6f7; margin:0; padding:0; }
.container { max-width:1200px; margin:auto; padding:20px; }
.container form { display:flex; gap:15px; align-items:center; flex-wrap:wrap; margin-bottom:20px; }
.container input, .container select { padding:5px; }
.char-card { background:#fff; padding:15px; margin-bottom:15px; border-radius:10px; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
.char-card form input { margin:5px; padding:5px; }
.bar { background:#ddd; border-radius:5px; margin:5px 0; height:20px; overflow:hidden;}
.hp-bar { background:red; height:100%; text-align:center; color:#fff; transition: width 0.5s;}
.mana-bar { background:blue; height:100%; text-align:center; color:#fff; transition: width 0.5s;}
.power-bar { background:gold; height:100%; text-align:center; color:#000; transition: width 0.5s;}
button { padding:5px 10px; cursor:pointer; border:none; border-radius:5px; background:#2ecc71; color:#fff;}
@media(max-width:768px){
    .char-card form input { width:100%; display:block; }
    .bar { font-size:12px; }
    .container form { flex-direction:column; align-items:flex-start; }
}
</style>
