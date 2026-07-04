<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Buscar personagens do jogador
$stmt = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID = ?", [$playerID]);
$chars = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $chars[] = $row;
}

if(empty($chars)){
    die("<p>Você não tem personagens.</p><a href='dashboard.php'>⬅️ Voltar</a>");
}

// Seleção do personagem
$charID = isset($_POST['charID']) ? intval($_POST['charID']) : $chars[0]['CharID'];

// Buscar inventário do personagem
$stmtItems = sqlsrv_query($conn, "SELECT * FROM Items WHERE CharID = ?", [$charID]);
$items = [];
while($row = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC)){
    $items[] = $row;
}

// Lista de slots
$slots = ['Arma','Escudo','Capacete','Armadura','Gluva','Calça','Asa','Pet','Anel1','Pingente','Anel2','Colar'];

// Equipar item
$mensagem = "";
if($_POST && isset($_POST['equip_action'])){
    $slot = $_POST['slot'];
    $itemID = intval($_POST['itemID']);

    // Atualizar item no slot
    $sql = "UPDATE Items SET Type = ? WHERE ItemID = ? AND CharID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$slot, $itemID, $charID]);

    if($stmt){
        $mensagem = "✅ Item equipado no slot $slot!";
    } else {
        $mensagem = "❌ Erro ao equipar item: " . print_r(sqlsrv_errors(), true);
    }

    // Atualizar inventário
    $stmtItems = sqlsrv_query($conn, "SELECT * FROM Items WHERE CharID = ?", [$charID]);
    $items = [];
    while($row = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC)){
        $items[] = $row;
    }
}

// Função para mostrar item no slot
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
<title>Equipar Itens - Mumu</title>
<style>
body { background:#222; color:#f1f1f1; font-family:Arial; text-align:center; padding:20px; }
h1,h2 { color:#ffcc00; }
select, button, a { padding:8px 15px; margin:5px; border:none; border-radius:5px; background:#444; color:#fff; cursor:pointer; transition:0.3s; text-decoration:none; }
button:hover, a:hover, select:hover { background:#ffcc00; color:#000; }
.inventario { display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; max-width:700px; margin:20px auto; text-align:left; }
.slot { background:#333; padding:10px; border-radius:5px; }
.slot span { font-weight:bold; color:#ffcc00; }
</style>
</head>
<body>
<h1>🛡️ Equipar Itens</h1>

<?php if($mensagem) echo "<p>$mensagem</p>"; ?>

<form method="post">
    <select name="charID" onchange="this.form.submit()">
        <?php foreach($chars as $c): ?>
            <option value="<?= $c['CharID'] ?>" <?= $c['CharID']==$charID?'selected':'' ?>>
                <?= $c['Name'] ?> | Level <?= $c['Level'] ?>
            </option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit">Selecionar</button></noscript>
</form>

<h2>Inventário</h2>
<div class="inventario">
<?php foreach($slots as $slot): 
    $item = getItem($items,$slot); ?>
    <div class="slot">
        <span><?= $slot ?>:</span> <?= $item ? $item['Name'] : "-" ?>
        <?php if($item): ?>
            <form method="post" style="margin-top:5px;">
                <input type="hidden" name="charID" value="<?= $charID ?>">
                <input type="hidden" name="slot" value="<?= $slot ?>">
                <input type="hidden" name="itemID" value="<?= $item['ItemID'] ?>">
                <button type="submit" name="equip_action">Equipar</button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<h2>Itens Disponíveis</h2>
<div class="inventario">
<?php foreach($items as $i): ?>
    <div class="slot">
        <span><?= $i['Name'] ?></span><br>
        Tipo: <?= $i['Type'] ?: "Não equipado" ?>
    </div>
<?php endforeach; ?>
</div>

<a href="dashboard.php">⬅️ Voltar</a>
</body>
</html>
