<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])) header("Location: login.php");
$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID=?", [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role'] !== 'admin') die("⛔ Acesso negado.");

if(!isset($_POST['CharID'])) die("❌ Personagem não especificado.");
$charID = intval($_POST['CharID']);

$stmtChar = sqlsrv_query($conn, "SELECT * FROM Characters WHERE CharID=?", [$charID]);
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
if(!$char) die("❌ Personagem não encontrado.");

if(isset($_POST['Name'], $_POST['Class'], $_POST['Level'], $_POST['Reset'], $_POST['MReset'])){
    sqlsrv_query($conn, "UPDATE Characters SET Name=?, Class=?, Level=?, Reset=?, MReset=? WHERE CharID=?", [
        $_POST['Name'], $_POST['Class'], intval($_POST['Level']), intval($_POST['Reset']), intval($_POST['MReset']), $charID
    ]);
    header("Location: admin_personagens.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Personagem</title>
<style>
body{background:#1c1c1c;color:#fff;font-family:Arial;text-align:center;padding:50px;}
form{background:#333;padding:20px;border-radius:10px;display:inline-block;}
input, select{padding:10px;margin:5px 0;width:200px;border-radius:5px;border:none;}
button{padding:10px 20px;margin-top:10px;border:none;border-radius:5px;background:#ffaa00;color:#000;cursor:pointer;}
button:hover{background:#ffcc33;}
</style>
</head>
<body>
<h1>✏ Editar Personagem ID <?= $charID ?></h1>
<form method="post">
<input type="hidden" name="CharID" value="<?= $charID ?>">
<label>Nome:</label><br><input type="text" name="Name" value="<?= htmlspecialchars($char['Name']) ?>" required><br>
<label>Classe:</label><br><input type="text" name="Class" value="<?= htmlspecialchars($char['Class']) ?>" required><br>
<label>Nível:</label><br><input type="number" name="Level" value="<?= $char['Level'] ?>" required><br>
<label>Reset:</label><br><input type="number" name="Reset" value="<?= $char['Reset'] ?>" required><br>
<label>MReset:</label><br><input type="number" name="MReset" value="<?= $char['MReset'] ?>" required><br>
<button type="submit">💾 Salvar Alterações</button>
</form>
<!-- Botão de excluir personagem -->
<form method="post" action="excluir_personagem.php" onsubmit="return confirm('⚠ Tem certeza que deseja excluir este personagem? Esta ação não pode ser desfeita.');">
    <input type="hidden" name="CharID" value="<?= $charID ?>">
    <button type="submit" style="background:#ff4444;color:#fff;">🗑️ Excluir Personagem</button>
</form>



<p><a href="admin_personagens.php" style="color:#ffcc00;">⬅ Voltar</a></p>
</body>
</html>
