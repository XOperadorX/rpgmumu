<?php
session_start();
include "db.php";

// ===== Verifica login e admin =====
if (!isset($_SESSION['PlayerID'])) {
    header("Location: login.php");
    exit;
}

$adminID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn, "SELECT Username, Role FROM Players WHERE PlayerID = ?", [$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row || $row['Role'] !== 'admin') {
    die("⛔ Acesso negado. Apenas administradores podem acessar.");
}

$adminName = $row['Username'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Jogadores - Admin</title>
<style>
body { background:#1c1c1c; color:#fff; font-family:Arial, sans-serif; margin:0; }
header { background:#cc0000; padding:20px; text-align:center; }
header h1 { margin:0; font-size:26px; }
nav { background:#222; padding:15px; text-align:center; }
nav a { margin:5px; padding:10px 15px; border-radius:5px; background:#444; color:#fff; text-decoration:none; font-weight:bold; display:inline-block; transition:0.3s; }
nav a:hover { background:#666; }
main { padding:20px; overflow-x:auto; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:10px; border:1px solid #444; text-align:center; }
th { background:#cc0000; }
tr:nth-child(even){background:#2a2a2a;}
.status-ok { color:#00ff00; font-weight:bold; }
.status-block { color:#ff3333; font-weight:bold; }
button { padding:5px 10px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; }
.btn-toggle { background:#ffaa00; color:#000; }
.btn-toggle:hover { background:#ffcc33; }
.msg { text-align:center; margin-top:10px; color:orange; }
@media(max-width:600px){th, td{font-size:12px; padding:5px;} nav a{padding:6px 10px;}}
</style>
</head>
<body>
<header>
<h1>👥 Gerenciar Jogadores</h1>
<p>Admin: <?= htmlspecialchars($adminName) ?></p>
</header>

<nav>
  <a href="../admin_dashboard.php">⬅ Voltar</a>
  <a href="../logout.php">🚪 Sair</a>
</nav>

<main>
<h2>Lista de Jogadores</h2>
<div id="msg" class="msg"></div>
<div id="table-container">
<!-- Tabela carregada via AJAX -->
</div>
</main>

<script>
// Função para carregar jogadores via AJAX
function loadPlayers(){
    fetch('fetch_ajax_players.php')
    .then(res => res.text())
    .then(html => {
        document.getElementById('table-container').innerHTML = html;
        attachEvents();
    });
}

// Anexa eventos de bloqueio/desbloqueio
function attachEvents(){
    document.querySelectorAll('.btn-toggle').forEach(btn=>{
        btn.addEventListener('click', function(){
            const playerID = this.dataset.playerid;
            fetch('admin_toggle_block_ajax.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'playerID='+playerID
            })
            .then(resp=>resp.json())
            .then(data=>{
                document.getElementById('msg').innerText = data.message;
                loadPlayers();
            });
        });
    });
}

// Inicial
loadPlayers();
</script>
</body>
</html>
