<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])) die("Acesso negado");

if(isset($_POST['ItemID']) && isset($_POST['Slot'])){
    $itemID = intval($_POST['ItemID']);
    $slot = $_POST['Slot'];

    // Atualiza slot do item
    $sql = "UPDATE Items SET EquippedSlot=? WHERE ItemID=?";
    sqlsrv_query($conn, $sql, [$slot, $itemID]);
}
?>
