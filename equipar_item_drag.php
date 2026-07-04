<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Buscar personagens
$stmt = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID = ?", [$playerID]);
$chars = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $chars[] = $row;
}

if(empty($chars)){
    die("<p>Você não tem personagens.</p><a href='dashboard.php'>⬅️ Voltar</a>");
}

// Selecionar personagem
$charID = isset($_POST['charID']) ? intval($_POST['charID']) : $chars[0]['CharID'];

// Buscar itens do inventário
$stmtItems = sqlsrv_query($conn, "SELECT * FROM Items WHERE CharID = ?", [$charID]);
$items = [];
while($row = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC)){
    $items[] = $row;
}

// Slots equipáveis
$slots = ['Arma','Escudo','Capacete','Armadura','Gluva','Calça','Asa','Pet','Anel1','Pingente','Anel2','Colar'];

// Equipar item via Ajax
if(isset($_POST['equipItem'])){
    $slot = $_POST['slot'];
    $itemID = intval($_POST['itemID']);
    $sql = "UPDATE Items SET Type = ? WHERE ItemID = ? AND CharID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$slot,$itemID,$charID]);
    echo $stmt ? "ok" : print_r(sqlsrv_errors(),true);
    exit;
}

// Funções
function getItem($items,$slot){
    foreach($items as $i){
        if($i['Type'] === $slot) return $i;
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Equipar Itens Drag & Drop - Mumu</title>
<style>
body { background:#222; color:#f1f1f1; font-family:Arial; text-align:center; padding:20px; }
h1,h2 { color:#ffcc00; }
select, a { padding:8px 15px; margin:5px; border:none; border-radius:5px; background:#444; color:#fff; cursor:pointer; text-decoration:none; }
select:hover, a:hover { background:#ffcc00; color:#000; }
.inventario, .slots { display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; max-width:700px; margin:20px auto; text-align:center; }
.item, .slot { background:#333; padding:10px; border-radius:5px; min-height:50px; display:flex; align-items:center; justify-content:center; cursor:pointer; }
.item { border:1px solid #555; }
.slot { border:2px dashed #555; transition:0.3s; }
.slot.hover { border-color:#ffcc00; }
.slot span { font-weight:bold; color:#ffcc00; }
</style>
</head>
<body>

<nav style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <form method="post" style="margin:0; display:flex; gap:10px;">
        <button type="submit" class="btn" name="refresh">🔄 Atualizar</button>
        <a href="dashboard.php" class="btn">⬅️ Voltar</a>
    </form>
</nav>
<h1>🛡️ Equipar Itens (Drag & Drop)</h1>

<form method="post">
    <select name="charID" onchange="this.form.submit()">
        <?php foreach($chars as $c): ?>
            <option value="<?= $c['CharID'] ?>" <?= $c['CharID']==$charID?'selected':'' ?>>
                <?= $c['Name'] ?> | Level <?= $c['Level'] ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<h2>Slots Equipáveis</h2>
<div class="slots">
<?php foreach($slots as $slot):
    $item = getItem($items,$slot); ?>
    <div class="slot" data-slot="<?= $slot ?>">
        <span><?= $slot ?></span><br>
        <?= $item ? $item['Name'] : "-" ?>
    </div>
<?php endforeach; ?>
</div>

<h2>Itens Disponíveis</h2>
<div class="inventario">
<?php foreach($items as $i):
    if($i['Type']) continue; // só mostrar itens não equipados ?>
    <div class="item" draggable="true" data-item="<?= $i['ItemID'] ?>"><?= $i['Name'] ?></div>
<?php endforeach; ?>
</div>

<a href="dashboard.php">⬅️ Voltar</a>

<script>
const items = document.querySelectorAll('.item');
const slots = document.querySelectorAll('.slot');

items.forEach(item => {
    item.addEventListener('dragstart', e => {
        e.dataTransfer.setData('itemID', item.dataset.item);
    });
});

slots.forEach(slot => {
    slot.addEventListener('dragover', e => {
        e.preventDefault();
        slot.classList.add('hover');
    });
    slot.addEventListener('dragleave', () => {
        slot.classList.remove('hover');
    });
    slot.addEventListener('drop', e => {
        e.preventDefault();
        slot.classList.remove('hover');
        const itemID = e.dataTransfer.getData('itemID');
        const slotName = slot.dataset.slot;

        // Ajax para equipar item
        fetch('equipar_item_drag.php', {
            method:'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body:`equipItem=1&itemID=${itemID}&slot=${slotName}&charID=<?= $charID ?>`
        }).then(res=>res.text()).then(res=>{
            if(res.trim() === 'ok'){
                location.reload();
            } else {
                alert('Erro: '+res);
            }
        });
    });
});
</script>
</body>
</html>
