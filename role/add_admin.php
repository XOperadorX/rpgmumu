<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])) exit('⛔ Acesso negado');

$adminID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn,"SELECT Role FROM Players WHERE PlayerID=?",[$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role']!=='admin') exit('⛔ Acesso negado');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if(!$username || !$password) exit('❌ Preencha todos os campos');

    // Verifica se o username já existe
    $check = sqlsrv_query($conn, "SELECT PlayerID FROM Players WHERE Username = ?", [$username]);
    if(sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC)){
        exit('❌ Username já existe');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Players (Username, PasswordHash, Role, CreatedAt) VALUES (?, ?, 'admin', GETDATE())";
    $stmt = sqlsrv_query($conn, $sql, [$username, $hash]);

    if($stmt){
        echo "✅ ADM criado com sucesso!";
    } else {
        echo "❌ Erro ao criar ADM: " . print_r(sqlsrv_errors(), true);
    }
}
?>
