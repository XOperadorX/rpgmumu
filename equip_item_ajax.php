<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado");
}

if(isset($_POST['ItemID'], $_POST['Slot'])){
    $itemID = intval($_POST['ItemID']);
    $slot = $_POST['Slot'];

    // Atualiza o slot do item
    $sql = "UPDATE Items SET EquippedSlot=? WHERE ItemID=?";
    sqlsrv_query($conn, $sql, [$slot, $itemID]);

    echo "Item equipado no slot: $slot";
}else{
    echo "Parâmetros inválidos";
}
