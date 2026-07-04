<?php
session_start();
include "db.php";
if(!isset($_SESSION['PlayerID'])) header("Location: login.php");

// Verifica se é admin
$stmt = sqlsrv_query($conn, "SELECT Username, Role FROM Players WHERE PlayerID=?", [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role'] !== 'admin') die("⛔ Acesso negado.");
$username = $row['Username'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Painel Admin - Mumu RPG</title>
<style>
body { margin:0; font-family:Arial; background:#1c1c1c; color:#fff; }
header { background:#cc0000; padding:20px; text-align:center; }
main { padding:40px; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; }
.card { background:#2a2a2a; padding:20px; border-radius:12px; text-align:center; transition:0.3s; }
.card:hover { background:#3a3a3a; transform:translateY(-5px); }
.card a, .card button { color:#fff; text-decoration:none; font-size:18px; font-weight:bold; display:block; border:none; background:none; cursor:pointer; }
.logout { text-align:center; margin-top:30px; }
.logout a { background:#ff3333; color:#fff; padding:12px 25px; border-radius:8px; text-decoration:none; font-weight:bold; }
.logout a:hover { background:#ff0000; }
.btn { display:inline-block; padding:12px 20px; background:#ffcc00; color:#000; font-weight:bold; border-radius:8px; cursor:pointer; margin-top:10px; transition:0.3s; }
.btn:hover { background:#ffd633; }
input { padding:8px; margin:5px 0; width:90%; border-radius:5px; border:none; }
#codigoGerado, #admMsg { margin-top:10px; font-weight:bold; color:lime; }
</style>
</head>
<body>
<header>
<h1>⚔️ Painel Administrativo</h1>
<p>Bem-vindo, <?= htmlspecialchars($username) ?>!</p>
</header>
<main>
<div class="card"><a href="admin_settings.php">⚙️ Configurações</a></div>
<div class="card"><a href="bank/admin_bank.php">🏦 Banco</a></div>
<div class="card"><a href="admin_logs.php">📜 Logs</a></div>
<div class="card"><a href="admin_players.php">🛡️ Conta de Players</a></div>
<div class="card"><a href="admin_list.php">🛡️ Editar As Contas</a></div>
<div class="card"><a href="admin_Characters_Edit.php">👥 Editar As Players</a></div>
<div class="card"><a href="enemies.php">👥  Enemies</a></div>
<div class="card"><a href="enemy_admin.php">👥 Adicionar Enemies</a></div>
<div class="card"><a href="manage_loot.php">👥 manage_loot.php</a></div>
<div class="card"><a href="del/admin_lista_contas.php">👥 admin_lista_contas.php</a></div>
<div class="card"><a href="mercado/admin_mercado.php">👥 manage_loot.php</a></div>

<!-- Gerar Código -->
<div class="card">
    <button id="btnGerarCodigo">🔑 Gerar Código</button>
    <div id="codigoGerado"></div>
</div>

<!-- Adicionar ADM -->
<div class="card">
    <h3>🛡️ Adicionar ADM</h3>
    <input type="text" id="admUsername" placeholder="Usuário">
    <input type="password" id="admPassword" placeholder="Senha">
    <button id="btnAddADM">Criar ADM</button>
    <div id="admMsg"></div>
</div>
</main>

<div class="logout"><a href="logout.php">🚪 Sair</a></div>

<script>
// Gerar código
document.getElementById("btnGerarCodigo").addEventListener("click", function(){
    fetch("gera_codigo.php")
    .then(res => res.text())
    .then(data => {
        document.getElementById("codigoGerado").innerHTML = data;
    }).catch(err => {
        document.getElementById("codigoGerado").innerHTML = "❌ Erro ao gerar código";
        console.error(err);
    });
});

// Adicionar ADM
document.getElementById("btnAddADM").addEventListener("click", function(){
    let username = document.getElementById("admUsername").value;
    let password = document.getElementById("admPassword").value;

    if(!username || !password){
        document.getElementById("admMsg").innerHTML = "❌ Preencha todos os campos!";
        return;
    }

    fetch("add_admin.php", {
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"username="+encodeURIComponent(username)+"&password="+encodeURIComponent(password)
    })
    .then(res=>res.text())
    .then(data=>{
        document.getElementById("admMsg").innerHTML = data;
    }).catch(err=>{
        document.getElementById("admMsg").innerHTML = "❌ Erro ao criar ADM";
        console.error(err);
    });
});
</script>
</body>
</html>
