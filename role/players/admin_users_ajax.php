<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])) exit('⛔ Acesso negado');

$adminID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn,"SELECT Role FROM Players WHERE PlayerID=?",[$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role']!=='admin') exit('⛔ Acesso negado');

$action = $_GET['action'] ?? '';

if($action === 'list'){
    $sql = "SELECT PlayerID, Username, CreatedAt FROM Players WHERE Role='admin'";
    $res = sqlsrv_query($conn, $sql);
    $admins = [];
    while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
        $row['CreatedAt'] = $row['CreatedAt']->format('d/m/Y H:i');
        $admins[] = $row;
    }
    echo json_encode($admins);

} elseif($action === 'create' && $_SERVER['REQUEST_METHOD']==='POST'){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if(!$username || !$password) exit('<span class="error">❌ Preencha todos os campos</span>');

    // Evita duplicidade
    $check = sqlsrv_query($conn, "SELECT PlayerID FROM Players WHERE Username=?", [$username]);
    if(sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC)) exit('<span class="error">❌ Username já existe</span>');

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO Players (Username, PasswordHash, Role, CreatedAt) VALUES (?, ?, 'admin', GETDATE())";
    if(sqlsrv_query($conn, $sql, [$username, $hash])){
        echo '<span class="success">✅ ADM criado com sucesso!</span>';
    } else echo '<span class="error">❌ Erro: '.print_r(sqlsrv_errors(),true).'</span>';

} elseif($action==='update' && $_SERVER['REQUEST_METHOD']==='POST'){
    $id = intval($_POST['PlayerID'] ?? 0);
    $username = trim($_POST['Username'] ?? '');
    if(!$id || !$username) exit('<span class="error">❌ Dados inválidos</span>');

    $check = sqlsrv_query($conn,"SELECT PlayerID FROM Players WHERE Username=? AND PlayerID<>?", [$username, $id]);
    if(sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC)) exit('<span class="error">❌ Username já existe</span>');

    $sql = "UPDATE Players SET Username=? WHERE PlayerID=?";
    if(sqlsrv_query($conn, $sql, [$username, $id])){
        echo '<span class="success">✅ Admin atualizado!</span>';
    } else echo '<span class="error">❌ Erro: '.print_r(sqlsrv_errors(),true).'</span>';

} elseif($action==='delete'){
    $id = intval($_GET['PlayerID'] ?? 0);
    if(!$id || $id==$adminID) exit('<span class="error">❌ Não é permitido excluir você mesmo</span>');

    $sql = "DELETE FROM Players WHERE PlayerID=? AND Role='admin'";
    if(sqlsrv_query($conn, $sql, [$id])){
        echo '<span class="success">✅ Admin excluído!</span>';
    } else echo '<span class="error">❌ Erro: '.print_r(sqlsrv_errors(),true).'</span>';
}
?>
