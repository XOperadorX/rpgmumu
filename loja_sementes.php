<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) {
    die("⛔ Faça login primeiro.");
}

$playerID = $_SESSION['PlayerID'];

// Busca saldo do jogador
$sql = "SELECT MoedaMumu FROM Players WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$moedas = $player['MoedaMumu'] ?? 0;

// Busca frutas disponíveis
$sql = "SELECT FrutaID, Nome, TempoCrescimento, PrecoSemente FROM Frutas";
$stmt = sqlsrv_query($conn, $sql);
$frutas = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $frutas[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>🌾 Loja de Sementes - RPG Fazenda</title>
<style>
body {
    font-family: 'Orbitron', Arial, sans-serif;
    background: #0d0d0d;
    color: #fff;
    text-align: center;
    padding: 20px;
}
.container {
    max-width: 900px;
    margin: auto;
}
.card {
    background: #1a1a1a;
    border: 2px solid #00ffcc;
    border-radius: 12px;
    padding: 20px;
    margin: 10px;
    box-shadow: 0 0 15px #00ffcc44;
    display: inline-block;
    width: 250px;
}
.card h3 {
    color: #00ffcc;
}
button {
    background: #00ffcc;
    color: #000;
    border: none;
    border-radius: 8px;
    padding: 10px 15px;
    cursor: pointer;
    font-weight: bold;
}
button:hover {
    background: #00ffaa;
}
.moedas {
    font-size: 1.2em;
    color: #ffeb3b;
    margin-bottom: 20px;
}
</style>
</head>
<body>
    <h1>🌱 Loja de Sementes</h1>
    <p class="moedas">💰 Suas Moedas Mumu: <span id="saldo"><?= $moedas ?></span></p>
    <div class="container">
        <?php foreach($frutas as $f): ?>
            <div class="card">
                <h3><?= htmlspecialchars($f['Nome']) ?></h3>
                <p>⏳ Crescimento: <?= (int)$f['TempoCrescimento'] ?> min</p>
                <p>💵 Preço da Semente: <?= (int)$f['PrecoSemente'] ?> Moedas</p>
                <input type="number" id="qtd<?= $f['FrutaID'] ?>" value="1" min="1" style="width:60px;">
                <button onclick="comprarSemente(<?= $f['FrutaID'] ?>, <?= (int)$f['PrecoSemente'] ?>)">Comprar</button>
            </div>
        <?php endforeach; ?>
    </div>

<script>
// Função para comprar semente
function comprarSemente(frutaID, preco){
    const qtd = parseInt(document.getElementById('qtd'+frutaID).value);
    if(isNaN(qtd) || qtd <= 0){
        alert("❗ Quantidade inválida.");
        return;
    }

    const total = preco * qtd;
    if(!confirm(`Deseja comprar ${qtd} semente(s) por ${total} MoedaMumu?`)) return;

    fetch('comprar_semente.php', {
        method: 'POST',
        body: new URLSearchParams({ frutaID, quantidade: qtd })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if(data.novoSaldo !== undefined){
            document.getElementById('saldo').textContent = data.novoSaldo;
        }
    });
}
</script>
</body>
</html>
