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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
body {
    background:#1c1c1c;
    color:#fff;
    font-family: Arial, sans-serif;
    padding:20px;
    text-align:center;
}

h1, h2 {
    margin-bottom: 15px;
}

#moedas {
    color: #00ff00;
    font-weight:bold;
}

.slots-container, .inventory-container {
    display:flex;
    flex-wrap: wrap;
    justify-content:center;
    margin-bottom:20px;
}

.slot {
    border: 2px solid #555;
    border-radius: 8px;
    width: 120px;
    height: 40px;
    text-align: center;
    line-height: 40px;
    margin: 5px;
    background: #222;
    cursor: grab;
}

.item {
    border: 1px solid #888;
    padding: 5px 10px;
    margin: 5px;
    background: #444;
    color: #fff;
    border-radius: 5px;
    cursor: grab;
}

.trash-container {
    margin: 20px 0;
    text-align:center;
}

#trash {
    background:#900; 
    color:#fff; 
    border:2px solid #f00;
    display:flex;
    justify-content:center;
    align-items:center;
    font-weight:bold;
    height:50px;
    width:250px;
    margin:0 auto;
    border-radius:8px;
    cursor:pointer;
}

/* Botão voltar estilo Dashboard */
.btn-dashboard {
    display: inline-block;
    padding: 10px 20px;
    margin-top: 10px;
    margin-right:5px;
    background: #444;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    border: none;
}
.btn-dashboard:hover {
    background: #ffcc00;
    color: #000;
}
</style>
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

<h2>Equipamentos</h2>
<div class="slots-container">
<?php foreach($slots as $slot): ?>
    <div class="slot" id="slot_<?= $slot ?>"><?= $slot ?></div>
<?php endforeach; ?>
</div>

<h2>Inventário</h2>
<div class="inventory-container">
<?php foreach($itens as $item): ?>
    <div class="item" id="item_<?= $item['ItemID'] ?>" draggable="true"><?= $item['Name'] ?></div>
<?php endforeach; ?>
</div>

<div class="trash-container">
    <h2 style="color:#ff4444;">🗑️ Lixeira</h2>
    <div id="trash">Arraste aqui para vender</div>
</div>

<a href="dashboard.php" class="btn-dashboard">⬅️ Voltar</a>

<script>
$(document).ready(function(){

    function atualizarSlotVisual(slotDiv, itemID, itemNome){
        slotDiv.text(itemNome);
        slotDiv.data('itemID', itemID);
    }

    function atualizarInventarioVisual(itemDiv){
        itemDiv.show();
    }

    // Inventário -> slots
    $('.item').on('dragstart', function(e){
        e.originalEvent.dataTransfer.setData('itemID', $(this).attr('id').split('_')[1]);
        e.originalEvent.dataTransfer.setData('from', 'inventory');
    });

    // Slots -> drag
    $('.slot').on('dragstart', function(e){
        if($(this).data('itemID')){
            e.originalEvent.dataTransfer.setData('itemID', $(this).data('itemID'));
            e.originalEvent.dataTransfer.setData('from', 'slot');
            e.originalEvent.dataTransfer.setData('slotID', $(this).attr('id'));
        } else { e.preventDefault(); }
    }).attr('draggable', true);

    // Drop nos slots
    $('.slot').on('dragover', function(e){ e.preventDefault(); });

    $('.slot').on('drop', function(e){
        e.preventDefault();
        let itemID = e.originalEvent.dataTransfer.getData('itemID');
        let from = e.originalEvent.dataTransfer.getData('from');
        let slotName = $(this).attr('id').replace('slot_', '');
        let slotDiv = $(this);

        if(from === 'inventory'){
            let itemNome = $('#item_' + itemID).text();
            atualizarSlotVisual(slotDiv, itemID, itemNome);
            $('#item_' + itemID).hide();

            $.post('equip_item_ajax.php', {ItemID: itemID, Slot: slotName});
        } else if(from === 'slot'){
            let oldSlotID = e.originalEvent.dataTransfer.getData('slotID');
            let itemNome = $('#' + oldSlotID).text();

            $('#' + oldSlotID).text(oldSlotID.replace('slot_',''));
            $('#' + oldSlotID).removeData('itemID');

            atualizarSlotVisual(slotDiv, itemID, itemNome);

            $.post('equip_item_ajax.php', {ItemID: itemID, Slot: slotName});
        }
    });

    // Inventário recebe itens de slots
    $('.inventory-container').on('dragover', function(e){ e.preventDefault(); });

    $('.inventory-container').on('drop', function(e){
        e.preventDefault();
        let itemID = e.originalEvent.dataTransfer.getData('itemID');
        let from = e.originalEvent.dataTransfer.getData('from');
        let slotID = e.originalEvent.dataTransfer.getData('slotID');

        if(from === 'slot'){
            $('#' + slotID).text(slotID.replace('slot_',''));
            $('#' + slotID).removeData('itemID');
            atualizarInventarioVisual($('#item_' + itemID));

            $.post('unequip_item_ajax.php', {ItemID: itemID});
        }
    });

    // Lixeira
    $('#trash').on('dragover', function(e){ e.preventDefault(); });

    $('#trash').on('drop', function(e){
        e.preventDefault();
        let itemID = e.originalEvent.dataTransfer.getData('itemID');
        let from = e.originalEvent.dataTransfer.getData('from');
        let slotID = e.originalEvent.dataTransfer.getData('slotID');

        if(from === 'inventory'){
            $('#item_' + itemID).fadeOut(300, function(){ $(this).remove(); });
        } else if(from === 'slot'){
            $('#' + slotID).fadeOut(300, function(){ $(this).remove(); });
        }

        $.post('sell_item_ajax.php', {ItemID: itemID}, function(res){
            if(res.moedas !== undefined){
                $('#moedas').text(res.moedas);
            }
            alert(res.msg);
        }, 'json');
    });

});
</script>
</body>
</html>
