<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) header("Location: login.php");

$playerID = $_SESSION['PlayerID'];

// Exibir status saúde
include 'saude_status.php';

// Comprar Items de saúde
if(isset($_POST['comprar'])){
    include 'saude_comprar.php';
}
?>
<form method="post">
    <button name="comprar">Comprar Poção (+100 HP)</button>
</form>
