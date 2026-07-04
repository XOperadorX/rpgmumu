<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Pega o personagem principal (ou o primeiro)
$stmt = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID=?", [$playerID]);
if(!$stmt || !sqlsrv_has_rows($stmt)){
    die("Você precisa ter pelo menos um personagem.<br><a href='dashboard.php'>⬅️ Voltar</a>");
}
$char = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Pega Items do personagem
$stmtItems = sqlsrv_query($conn, "SELECT * FROM Items WHERE CharID=?", [$char['CharID']]);
$Items = [];
while($row = sqlsrv_fetch_array($stmtItems, SQLSRV_FETCH_ASSOC)){
    $Items[] = $row;
}

// Slots padrão
$slots = ['Arma','Escudo','Capacete','Armadura','Gluva','Calça','Asa','Pet','Anel1','Pingente','Anel2','Colar'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Equipar Items</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script defer>
document.addEventListener("DOMContentLoaded", function() {
    let items = document.querySelectorAll(".item");
    let slots = document.querySelectorAll(".slot");

    items.forEach(item => {
        item.draggable = true;
        item.addEventListener("dragstart", e => {
            e.dataTransfer.setData("text/plain", item.id);
        });
    });

    slots.forEach(slot => {
        slot.addEventListener("dragover", e => {
            e.preventDefault();
            slot.classList.add("hover");
        });
        slot.addEventListener("dragleave", e => {
            slot.classList.remove("hover");
        });
        slot.addEventListener("drop", e => {
            e.preventDefault();
            slot.classList.remove("hover");
            const id = e.dataTransfer.getData("text/plain");
            const draggedItem = document.getElementById(id);

            if(slot.children.length === 1){ // slot vazio
                slot.appendChild(draggedItem);
                draggedItem.classList.add("fade-out");
                setTimeout(() => draggedItem.classList.remove("fade-out"), 300);

                // Salvar no banco via AJAX
                let itemID = id.split("_")[1];
                let slotName = slot.id.replace("slot_", "");
                $.post("equip_save.php", {ItemID: itemID, Slot: slotName});
            }
        });
    });
});
</script>
</head>
<body>
<h2>⚔️ Equipar Items - <?= $char['Name'] ?></h2>

<div class="inventario">
    <h3>Inventário</h3>
    <?php foreach($Items as $item): ?>
        <?php if(empty($item['EquippedSlot'])): ?>
            <div class="item" id="item_<?= $item['ItemID'] ?>"><?= $item['Name'] ?></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<div class="slots">
    <h3>Slots do Personagem</h3>
    <?php foreach($slots as $slot): ?>
        <div class="slot" id="slot_<?= $slot ?>">
            <span><?= $slot ?></span>
            <?php
            foreach($Items as $item){
                if($item['EquippedSlot'] === $slot){
                    echo "<div class='item-in-slot' id='item_{$item['ItemID']}'>{$item['Name']}</div>";
                }
            }
            ?>
        </div>
    <?php endforeach; ?>
</div>

<a href="dashboard.php">⬅️ Voltar</a>
</body>
</html>
