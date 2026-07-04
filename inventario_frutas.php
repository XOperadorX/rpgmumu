<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("⛔ Faça login primeiro.");
}

$playerID = $_SESSION['PlayerID'];

$sql = "SELECT i.FrutaID, f.Nome, i.Quantidade, f.PrecoVenda
        FROM InventarioFrutas i
        JOIN Frutas f ON i.FrutaID = f.FrutaID
        WHERE i.PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);

$frutas = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $frutas[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>🍇 Inventário de Frutas</title>
<style>
body {
    font-family: 'Orbitron', Arial, sans-serif;
    background: #0a0a0a;
    color: #fff;
    text-align: center;
    padding: 20px;
}
.container {
    max-width: 800px;
    margin: auto;
}
.card {
    background: #111;
    border: 2px solid #00ffcc;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 0 15px #00ffcc55;
}
.btn {
    background: #00ffcc;
    color: #000;
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    font-weight: bold;
}
.btn:hover { background: #00ffaa; }
input {
    width: 50px;
    text-align: center;
    border: 1px solid #00ffcc;
    background: #111;
    color: #fff;
    border-radius: 5px;
    margin-left: 10px;
}
</style>
</head>
<body>

<h1>🍓 Inventário de Frutas</h1>
<div class="container">
<?php if(empty($frutas)): ?>
    <p>Nenhuma fruta no inventário 🍂</p>
<?php else: ?>
    <?php foreach($frutas as $f): ?>
        <div class="card">
            <div>
                <strong><?= htmlspecialchars($f['Nome']) ?></strong><br>
                <small>Qtd: <?= $f['Quantidade'] ?> | Valor: 💰 <?= $f['PrecoVenda'] ?></small>
            </div>
            <div>
                <input type="number" id="qtd<?= $f['FrutaID'] ?>" min="1" max="<?= $f['Quantidade'] ?>" value="1">
                <button class="btn" onclick="vender(<?= $f['FrutaID'] ?>, <?= $f['PrecoVenda'] ?>)">Vender</button>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script>
function vender(frutaID, preco){
    const qtd = parseInt(document.getElementById('qtd'+frutaID).value);
    if(isNaN(qtd) || qtd <= 0){ alert("❗ Quantidade inválida"); return; }

    fetch('vender_fruta.php', {
        method:'POST',
        body:new URLSearchParams({frutaID, quantidade:qtd, preco})
    }).then(r=>r.json()).then(data=>{
        alert(data.message);
        location.reload();
    });
}
</script>

</body>
</html>
