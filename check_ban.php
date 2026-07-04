<?php
if (!isset($_SESSION['PlayerID'])) {
    header("Location: login.php");
    exit;
}

$sql = "SELECT IsBanned FROM Players WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row || $row['IsBanned']) {
    session_destroy();
    die("⛔ Você está banido e não pode acessar o jogo.");
}
?>
