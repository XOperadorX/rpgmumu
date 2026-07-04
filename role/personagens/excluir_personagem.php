<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])) header("Location: login.php");

$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID=?", [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role'] !== 'admin') die("⛔ Acesso negado.");

if(!isset($_POST['CharID'])) die("❌ Personagem não especificado.");
$charID = intval($_POST['CharID']);

// Verifica se o personagem existe
$stmtChar = sqlsrv_query($conn, "SELECT * FROM Characters WHERE CharID=?", [$charID]);
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
if(!$char) die("❌ Personagem não encontrado.");

// Exclui o personagem
sqlsrv_query($conn, "DELETE FROM Characters WHERE CharID=?", [$charID]);

header("Location: admin_personagens.php");
exit;
?>
