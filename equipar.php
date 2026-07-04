<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Pega personagem principal
$stmt = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID=?", [$playerID]);
if(!$stmt || !sqlsrv_has_rows($stmt)){
    die("Você precisa ter pelo menos um personagem.<br><a href='dashboard.php'>⬅️ Voltar</a>");
}
$char = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Pega itens do personagem
$stmtItems = sqlsrv_query($conn, "SELECT * FROM Items WHERE CharID=?", [$char['CharID']]);
$itens = [];
while($row = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC)){
    $itens[] = $row;
}

// Pega saldo de moedas
$stmtMoedas = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
$moedaRow = sqlsrv_fetch_array($stmtMoedas, SQLSRV_FETCH_ASSOC);
$moedas = $moedaRow['MoedaMumu'] ?? 0;

// Slots padrão
$slots = ['Arma','Escudo','Capacete','Armadura','Gluva','Calça','Asa','Pet','Anel1','Pingente','Anel2','Colar'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Equipar Itens Avançado</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
body {
    background:#1c1c1c;
    color:#fff;
    font-family: Arial, sans-serif;
    padding:20px;
}
.slot {
    border: 2px solid #555;
    border-radius: 8px;
    width: 120px;
    height: 40px;
    text-align: center;
    line-height: 40px;
    margin: 5px;
    display: inline-block;
    background: #222;
    color: #fff;
}
.item {
    border: 1px solid #888;
    padding: 5px 10px;
    margin: 5px;
    display: inline-block;
    cursor: grab;
    background: #444;
    color: #fff;
    border-radius: 5px;
}
#trash {
    border: 2px solid #f00;
    border-radius: 8px;
    width: 200px;
    height: 50px;
    text-align: center;
    line-height: 50px;
    margin: 10px 0;
    background: #900;
    color: #fff;
    font-weight:bold;
}
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function(){

    // arrastar itens
    $('.item').on('dragstart', function(e){
        e.originalEvent.dataTransfer.setData('itemID', $(this).attr('id').split('_')[1]);
    });

    // slots
    $('.slot').on('dragover', function(e){
        e.preventDefault();
    });

    $('.slot').on('drop', function(e){
        e.preventDefault();
        let itemID = e.originalEvent.dataTransfer.getData('itemID');
        let slotName = $(this).attr('id').replace('slot_', '');

        $(this).text($('#item_' + itemID).text());

        $.post('equip_item_ajax.php', {ItemID: itemID, Slot: slotName}, function(data){
            console.log(data);
        });
    });

    // lixeira
    $('#trash').on('dragover', function(e){
        e.preventDefault();
    });

    $('#trash').on('drop', function(e){
        e.preventDefault();
        let itemID = e.originalEvent.dataTransfer.getData('itemID');

        // remove do inventário com efeito
        $('#item_' + itemID).fadeOut(300, function(){ $(this).remove(); });

        // chama ajax para vender
        $.post('sell_item_ajax.php', {ItemID: itemID}, function(res){
            if(res.moedas !== undefined){
                $('#moedas').text(res.moedas); // atualiza saldo
            }
            alert(res.msg);
        }, 'json');
    });

});
</script>
</head>
<body>
<nav style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <form method="post" style="margin:0; display:flex; gap:10px;">
        <button type="submit" class="btn" name="refresh">🔄 Atualizar</button>
        <a href="dashboard.php" class="btn">⬅️ Voltar</a>
    </form>
</nav>

<h1>🎒 Equipar Itens - <?= $char['Name'] ?></h1>
<h2>💰 Moedas Mumu: <span id="moedas"><?= $moedas ?></span></h2>

<h2>Slots</h2>
<h2>🗑️ Lixeira</h2>
<div id="trash">Arraste aqui para vender</div>
<div>
<?php foreach($slots as $slot): ?>
    <div class="slot" id="slot_<?= $slot ?>"><?= $slot ?></div>
<?php endforeach; ?>
</div>

<h2>Inventário</h2>
<div>
<?php foreach($itens as $item): ?>
    <div class="item" id="item_<?= $item['ItemID'] ?>" draggable="true"><?= $item['Name'] ?></div>
<?php endforeach; ?>
</div>



<a href="dashboard.php">⬅️ Voltar</a>
</body>
</html>
