<?php
session_start();
include "db.php";
include "check_ban.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado.");
}

$playerID = $_SESSION['PlayerID'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inventário & Equipamento</title>
    <style>
        body { font-family: Arial, sans-serif; display:flex; padding:20px; }
        #inventario { width: 300px; }
        #equipamentos { flex:1; margin-left:50px; }

        .item-box {
            width:80px; height:80px; line-height:80px;
            text-align:center; margin:5px; border:3px solid black; border-radius:8px;
            font-weight:bold; cursor:pointer; display:inline-block;
        }
        .item-box:hover { background-color:#f0f0f0; }

        .slot {
            width:100px; height:100px; line-height:100px;
            text-align:center; margin:5px; border:3px solid gray; border-radius:8px;
            font-weight:bold; display:inline-block;
        }
        .slot-title { font-size:12px; margin-top:2px; text-align:center; }
    </style>
</head>
<body>

<div id="inventario">
    <h3>Inventário</h3>
    <div id="lista-itens">
        <!-- Itens serão carregados via AJAX -->
    </div>
</div>

<div id="equipamentos">
    <h3>Equipamentos do Personagem</h3>
    <div id="slots-personagem">
        <!-- Slots serão carregados via AJAX -->
    </div>
</div>

<script>
const cores = {
    'comum':'gray',
    'incomum':'green',
    'raro':'blue',
    'epico':'purple',
    'lendario':'orange'
};

// Carrega inventário
function carregarInventario(){
    fetch('inventario_ajax.php')
    .then(res => res.text())
    .then(html => {
        document.getElementById('lista-itens').innerHTML = html;
    });
}

// Carrega slots de equipamentos
function carregarEquipamentos(){
    fetch('mostrar_equipamentos_ajax.php')
    .then(res => res.text())
    .then(html => {
        document.getElementById('slots-personagem').innerHTML = html;
    });
}

// Equipar item
function equiparItem(itemID){
    fetch('equipamento.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'ItemID=' + itemID
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            carregarInventario();
            carregarEquipamentos();
        } else {
            alert('Erro: '+data.error);
        }
    });
}

// Inicializa
carregarInventario();
carregarEquipamentos();
</script>

</body>
</html>
