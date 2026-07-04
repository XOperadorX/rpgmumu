<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])) exit('⛔ Acesso negado');
$adminID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn,"SELECT Role FROM Players WHERE PlayerID=?",[$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role']!=='admin') exit('⛔ Acesso negado');

$action = $_GET['action'] ?? '';

if($action==='list'){
    $sql = "SELECT TOP 1000 PlayerID, Username, PasswordHash, MoedaMumu, CreatedAt, UpdatedAt, LastLoginIP, LastLoginTime, IsBanned, Role, CodigoUsado FROM Players";
    $res = sqlsrv_query($conn, $sql);
    $players = [];
    while($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)){
        $row['CreatedAt'] = $row['CreatedAt'] ? $row['CreatedAt']->format('d/m/Y H:i') : '';
        $row['UpdatedAt'] = $row['UpdatedAt'] ? $row['UpdatedAt']->format('d/m/Y H:i') : '';
        $row['LastLoginTime'] = $row['LastLoginTime'] ? $row['LastLoginTime']->format('d/m/Y H:i') : '';
        $players[] = $row;
    }
    echo json_encode($players);

} elseif($action==='update' && $_SERVER['REQUEST_METHOD']==='POST'){
    $id = intval($_POST['PlayerID']);
    if(!$id) exit('<span class="error">❌ ID inválido</span>');

    $sql = "UPDATE Players SET Username=?, MoedaMumu=?, LastLoginIP=?, IsBanned=?, Role=?, CodigoUsado=?, UpdatedAt=GETDATE() WHERE PlayerID=?";
    $params = [
        $_POST['Username'],
        $_POST['MoedaMumu'],
        $_POST['LastLoginIP'],
        $_POST['IsBanned'],
        $_POST['Role'],
        $_POST['CodigoUsado'],
        $id
    ];

    if(sqlsrv_query($conn, $sql, $params)){
        echo '<span class="success">✅ Jogador atualizado!</span>';
    } else echo '<span class="error">❌ Erro: '.print_r(sqlsrv_errors(),true).'</span>';
}

elseif($action==='delete'){
    $id = intval($_GET['PlayerID']);
    if(!$id) exit('<span class="error">❌ ID inválido</span>');

    // 1️⃣ Consulta todas as tabelas que têm FK para Players.PlayerID
    $sqlFK = "
        SELECT tp.name AS TableName
        FROM sys.foreign_keys fk
        JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
        JOIN sys.tables tp ON fk.parent_object_id = tp.object_id
        WHERE fkc.referenced_object_id = OBJECT_ID('Players')
    ";
    $resFK = sqlsrv_query($conn, $sqlFK);
    $tables = [];
    while($row = sqlsrv_fetch_array($resFK, SQLSRV_FETCH_ASSOC)){
        $tables[] = $row['TableName'];
    }

    // 2️⃣ Apaga registros dependentes de cada tabela
    foreach($tables as $table){
        $sqlDel = "DELETE FROM $table WHERE PlayerID=?";
        sqlsrv_query($conn, $sqlDel, [$id]);
    }

    // 3️⃣ Apaga o jogador
    $sqlPlayer = "DELETE FROM Players WHERE PlayerID=?";
    if(sqlsrv_query($conn, $sqlPlayer, [$id])){
        echo '<span class="success">✅ Jogador excluído com sucesso!</span>';
    } else {
        echo '<span class="error">❌ Erro ao excluir jogador: '.print_r(sqlsrv_errors(),true).'</span>';
    }
}

?>



