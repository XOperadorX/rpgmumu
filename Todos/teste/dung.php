<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) header("Location: login.php");

$playerID = $_SESSION['PlayerID'];

// Exemplo de dungeon: gerar inimigo aleatório
$inimigos = ["Goblin", "Orc", "Esqueleto"];
$inimigo = $inimigos[array_rand($inimigos)];

// Puxar stats do jogador
$sql = "SELECT HP, ATK, DEF FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, array($playerID));
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "<h2>Dungeon</h2>";
echo "<p>Você encontrou um $inimigo!</p>";

// Combate básico (um turno)
if(isset($_POST['atacar'])){
    $dano = max(0, $player['ATK'] - rand(1,5)); // dano simplificado
    $logSQL = "INSERT INTO DungeonLogs (PlayerID, Inimigo, Dano, DataHora) VALUES (?, ?, ?, GETDATE())";
    sqlsrv_query($conn, $logSQL, array($playerID, $inimigo, $dano));
    echo "<p>Você causou $dano de dano ao $inimigo!</p>";
}

?>
<form method="post">
    <button name="atacar">Atacar</button>
</form>

<a href="fetch_dungeon_log.php">Ver histórico</a>


<div id="dungeon">
<p id="status">Você encontrou um Goblin!</p>
<button onclick="atacar('Goblin')">Atacar</button>
<div id="logs"></div>
</div>

<script>
function atacar(inimigo){
    let dano = Math.floor(Math.random() * 10) + 1;
    fetch('log_dungeon.php', {
        method: 'POST',
        body: new URLSearchParams({inimigo: inimigo, dano: dano})
    }).then(res => res.text())
      .then(data => {
          if(data=="ok") atualizarLogs();
    });
}

function atualizarLogs(){
    fetch('fetch_dungeon_log.php')
    .then(res => res.text())
    .then(html => document.getElementById('logs').innerHTML = html);
}

setInterval(atualizarLogs, 5000); // Atualiza logs a cada 5s
</script>
