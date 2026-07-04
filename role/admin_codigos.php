<?php
session_start();
include "db.php";

// --- Somente admin pode acessar ---
if(!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin'){
    die("⛔ Acesso negado.");
}

// --- Gerar novo código ---
if(isset($_POST['gerar'])){
    $codigo = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    $sql = "INSERT INTO Codigos (Codigo, Usado) VALUES (?, 0)";
    sqlsrv_query($conn, $sql, [$codigo]);
}

// --- Deletar código ---
if(isset($_GET['del'])){
    $id = (int)$_GET['del'];
    $sql = "DELETE FROM Codigos WHERE ID=?";
    sqlsrv_query($conn, $sql, [$id]);
    header("Location: admin_codigos.php");
    exit;
}

// --- Listar códigos ---
$sql = "SELECT ID, Codigo, Usado FROM Codigos ORDER BY ID DESC";
$stmt = sqlsrv_query($conn, $sql);
$codigos = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $codigos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Admin - Gerenciar Códigos</title>
<style>
body{background:#111;color:#eee;font-family:Arial;text-align:center;padding:20px;}
table{margin:auto;border-collapse:collapse;width:60%;background:#222;}
th,td{border:1px solid #444;padding:10px;}
th{background:#333;}
button,a.btn{padding:8px 15px;border:none;border-radius:5px;background:#ffcc00;color:#000;cursor:pointer;text-decoration:none;}
button:hover,a.btn:hover{background:#ffd633;}
.status{font-weight:bold;}
.status.usado{color:#f55;}
.status.livre{color:#5f5;}
</style>
</head>
<body>
<h1>🔑 Gerenciar Códigos</h1>

<form method="post">
    <button type="submit" name="gerar">➕ Gerar Novo Código</button>
</form>

<table>
<tr>
    <th>ID</th>
    <th>Código</th>
    <th>Status</th>
    <th>Ações</th>
</tr>
<?php foreach($codigos as $c): ?>
<tr>
    <td><?= $c['ID'] ?></td>
    <td><strong><?= $c['Codigo'] ?></strong></td>
    <td class="status <?= $c['Usado'] ? 'usado' : 'livre' ?>">
        <?= $c['Usado'] ? '❌ Usado' : '✅ Livre' ?>
    </td>
    <td>
        <a class="btn" href="?del=<?= $c['ID'] ?>" onclick="return confirm('Deletar código?')">🗑️ Deletar</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<p><a href="dashboard.php" class="btn">⬅️ Voltar</a></p>
</body>
</html>
