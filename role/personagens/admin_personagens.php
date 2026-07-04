<?php
session_start();
include "db.php";

// Verifica login admin
if(!isset($_SESSION['PlayerID'])){
    header("Location: login.php");
    exit;
}

$stmt = sqlsrv_query($conn, "SELECT Role, Username FROM Players WHERE PlayerID=?", [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role'] !== 'admin') die("⛔ Acesso negado.");

$adminName = $row['Username'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Admin - Personagens</title>
<style>
body { background:#1c1c1c; color:#fff; font-family:Arial; margin:0; }
header { background:#0077cc; padding:20px; text-align:center; }
nav { background:#222; padding:15px; text-align:center; }
nav a { margin:0 10px; padding:12px 20px; border-radius:5px; background:#444; color:#fff; text-decoration:none; font-weight:bold; display:inline-block; transition:0.3s; }
nav a:hover { background:#666; }
main { padding:30px; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:10px; border:1px solid #444; text-align:center; }
th { background:#0077cc; }
tr:nth-child(even) { background:#2a2a2a; }
button { padding:5px 10px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; }
.btn-edit { background:#ffaa00; color:#000; }
.btn-edit:hover { background:#ffcc33; }
.btn-del { background:#cc0000; color:#fff; }
.btn-del:hover { background:#ff3333; }
.msg { color:orange; margin-top:10px; text-align:center; }
</style>
</head>
<body>
<header>
<h1>⚔️ Gerenciar Personagens</h1>
<p>Admin: <?= htmlspecialchars($adminName) ?></p>
</header>
<nav>
<a href="admin_dashboard.php">⬅ Voltar</a>
<a href="logout.php">🚪 Sair</a>
</nav>
<main>
<h2>Lista de Personagens</h2>
<div id="msg" class="msg"></div>
<div id="table-container">
<!-- Tabela será carregada via AJAX -->
</div>
</main>

<script>
// Função para buscar e renderizar personagens
function loadCharacters() {
    fetch('fetch_ajax_personagens.php')
    .then(response => response.text())
    .then(html => {
        document.getElementById('table-container').innerHTML = html;
        attachDeleteEvents(); // Anexa eventos aos botões de deletar
    })
    .catch(err => console.error(err));
}

// Função para anexar eventos de delete via AJAX
function attachDeleteEvents() {
    document.querySelectorAll('.btn-del').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            if(!confirm('⚠ Tem certeza que deseja excluir este personagem?')) return;
            const charID = this.dataset.charid;
            fetch('admin_personagem_delete.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'CharID='+charID
            })
            .then(resp => resp.json())
            .then(data => {
                document.getElementById('msg').innerText = data.message;
                loadCharacters();
            })
            .catch(err => console.error(err));
        });
    });
}

// Carrega inicialmente
loadCharacters();
</script>
</body>
</html>
