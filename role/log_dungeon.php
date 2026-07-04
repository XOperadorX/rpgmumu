<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Pega todos os personagens do jogador para o filtro
$stmt = sqlsrv_query($conn, "SELECT CharID, Name FROM Characters WHERE PlayerID=?", [$playerID]);
$characters = [];
if($stmt && sqlsrv_has_rows($stmt)){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $characters[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Log Dungeon</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
    body { font-family: Arial, sans-serif; background:#1c1c1c; color:#fff; padding:20px;}
    h2 { color:#ffcc00; }
    p { margin:5px 0; }
    .xp { color:#00ff00; font-weight:bold; }
    .item { color:#00bfff; font-weight:bold; }
    select { margin-right:10px; padding:5px; border-radius:5px; }

    /* Tabela */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size:14px;
    }
    table th, table td {
        border: 1px solid #444;
        padding: 8px;
        text-align: center;
    }
    table th {
        background: #333;
        color: #fff;
    }
    table tr:nth-child(even) { background: #2a2a2a; }
    table tr:hover { background: #444; transition:0.3s; }

    /* Botões estilo Dashboard */
    .btn-dashboard, .btn-action {
        display: inline-block;
        padding: 10px 20px;
        margin-top: 10px;
        margin-right:5px;
        background: #444;
        color: #fff;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
        border: none;
    }
    .btn-dashboard:hover, .btn-action:hover {
        background: #ffcc00;
        color: #000;
    }

    /* Responsividade */
    @media (max-width:768px){
        table, thead, tbody, th, td, tr { display:block; }
        table th { display:none; }
        table tr { margin-bottom:10px; border:1px solid #444; padding:5px; }
        table td { display:flex; justify-content:space-between; padding:5px; }
        table td::before { content: attr(data-label); font-weight:bold; }
    }
</style>
</head>
<body>
<nav style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <form method="post" style="margin:0; display:flex; gap:10px;">
        <button type="submit" class="btn" name="refresh">🔄 Atualizar</button>
        <a href="dashboard.php" class="btn">⬅️ Voltar</a>
    </form>
</nav>
<h2>📜 Log da Dungeon</h2>

<form id="filterForm">
    <select name="CharID" id="CharID">
        <option value="">Todos os Personagens</option>
        <?php foreach($characters as $char): ?>
            <option value="<?= $char['CharID'] ?>"><?= $char['Name'] ?></option>
        <?php endforeach; ?>
    </select>

    <select name="ItemType" id="ItemType">
        <option value="">Todos os Itens</option>
        <option value="Arma">Arma</option>
        <option value="Escudo">Escudo</option>
        <option value="Armadura">Armadura</option>
        <option value="Anel">Anel</option>
        <option value="Pingente">Pingente</option>
        <option value="Colar">Colar</option>
        <option value="Pet">Pet</option>
        <option value="Asa">Asa</option>
    </select>
</form>

<div style="margin-bottom:10px;">
    <button id="clearLogs" class="btn-action">🗑️ Limpar Logs</button>
    <button id="exportLogs" class="btn-action">📥 Exportar CSV</button>
</div>

<div id="logContainer">
    <!-- Logs serão carregados aqui via AJAX -->
</div>

<a href="dashboard.php" class="btn-dashboard">⬅️ Voltar</a>

<script>
// Limpar logs
$('#clearLogs').click(function(){
    if(confirm("Tem certeza que deseja limpar os logs selecionados?")){
        const charID = $('#CharID').val();
        $.post('clear_dungeon_log.php', { CharID: charID }, function(res){
            alert(res);
            loadDungeonLog();
        });
    }
});

// Exportar logs
$('#exportLogs').click(function(){
    const charID = $('#CharID').val();
    const itemType = $('#ItemType').val();
    window.location.href = `export_dungeon_log.php?CharID=${charID}&ItemType=${itemType}`;
});

// Função para carregar os logs
function loadDungeonLog() {
    const charID = $('#CharID').val();
    const itemType = $('#ItemType').val();
    
    $.ajax({
        url: 'fetch_dungeon_log.php',
        method: 'GET',
        data: { CharID: charID, ItemType: itemType },
        success: function(data) {
            $('#logContainer').html(data);
        }
    });
}

// Carrega logs ao abrir a página
loadDungeonLog();

// Atualiza a cada 10 segundos
setInterval(loadDungeonLog, 10000);

// Atualiza logs quando filtro mudar
$('#filterForm select').change(loadDungeonLog);
</script>
</body>
</html>
