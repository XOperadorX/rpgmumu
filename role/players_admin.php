<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])) exit('⛔ Acesso negado');

// Só admins podem acessar
$adminID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID=?", [$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role'] !== 'admin') exit('⛔ Acesso negado');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gestão de Jogadores</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; }
table { border-collapse: collapse; width:100%; background:#fff; }
th, td { padding:10px; border:1px solid #ccc; text-align:left; }
button { padding:5px 10px; margin:2px; cursor:pointer; }
.success { color:green; }
.error { color:red; }
input[type=text], input[type=number] { width:100px; padding:5px; }
</style>
</head>
<body>

<h2>Jogadores</h2>

<div id="message"></div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>MoedaMumu</th>
            <th>Criado em</th>
            <th>Atualizado em</th>
            <th>Último IP</th>
            <th>Último login</th>
            <th>Banido</th>
            <th>Role</th>
            <th>Código usado</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody id="playerList"></tbody>
</table>

<script>
// Buscar todos os jogadores
function fetchPlayers(){
    fetch('players_admin_ajax.php?action=list')
    .then(res=>res.json())
    .then(data=>{
        const tbody = document.getElementById('playerList');
        tbody.innerHTML = '';
        data.forEach(player=>{
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${player.PlayerID}</td>
                <td><input type="text" value="${player.Username}" data-id="${player.PlayerID}" class="editUsername"></td>
                <td><input type="number" value="${player.MoedaMumu}" data-id="${player.PlayerID}" class="editMoeda"></td>
                <td>${player.CreatedAt}</td>
                <td>${player.UpdatedAt}</td>
                <td><input type="text" value="${player.LastLoginIP}" data-id="${player.PlayerID}" class="editIP"></td>
                <td>${player.LastLoginTime}</td>
                <td>
                    <select data-id="${player.PlayerID}" class="editBanned">
                        <option value="0" ${player.IsBanned==0?'selected':''}>Não</option>
                        <option value="1" ${player.IsBanned==1?'selected':''}>Sim</option>
                    </select>
                </td>
                <td><input type="text" value="${player.Role}" data-id="${player.PlayerID}" class="editRole"></td>
                <td><input type="text" value="${player.CodigoUsado}" data-id="${player.PlayerID}" class="editCodigo"></td>
                <td>
                    <button class="updateBtn" data-id="${player.PlayerID}">Salvar</button>
                    <button class="deleteBtn" data-id="${player.PlayerID}">Excluir</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.querySelectorAll('.updateBtn').forEach(btn=>{
            btn.onclick = ()=> updatePlayer(btn.dataset.id);
        });
        document.querySelectorAll('.deleteBtn').forEach(btn=>{
            btn.onclick = ()=> deletePlayer(btn.dataset.id);
        });
    });
}

// Atualizar jogador
function updatePlayer(id){
    const formData = new FormData();
    formData.append('PlayerID', id);
    formData.append('Username', document.querySelector(`.editUsername[data-id="${id}"]`).value);
    formData.append('MoedaMumu', document.querySelector(`.editMoeda[data-id="${id}"]`).value);
    formData.append('LastLoginIP', document.querySelector(`.editIP[data-id="${id}"]`).value);
    formData.append('IsBanned', document.querySelector(`.editBanned[data-id="${id}"]`).value);
    formData.append('Role', document.querySelector(`.editRole[data-id="${id}"]`).value);
    formData.append('CodigoUsado', document.querySelector(`.editCodigo[data-id="${id}"]`).value);

    fetch('players_admin_ajax.php?action=update', { method:'POST', body: formData })
    .then(res=>res.text())
    .then(msg=>{
        document.getElementById('message').innerHTML = msg;
        fetchPlayers();
    });
}

// Excluir jogador
function deletePlayer(id){
    if(!confirm('Tem certeza que deseja excluir este jogador?')) return;
    fetch(`players_admin_ajax.php?action=delete&PlayerID=${id}`)
    .then(res=>res.text())
    .then(msg=>{
        document.getElementById('message').innerHTML = msg;
        fetchPlayers();
    });
}

// Inicializa
fetchPlayers();
</script>
</body>
</html>
