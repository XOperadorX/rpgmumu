<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID']) || $_SESSION['Role'] !== 'admin'){
    die("Acesso negado. Apenas administradores podem acessar.");
}

// Busca todos os personagens
$sql = "SELECT c.CharID, c.Name, c.Class, c.Level, c.Exp, c.HP, c.MaxHP,
               c.Arma, c.Escudo, c.Capacete, c.Armadura, c.Luva, c.Calça,
               p.PlayerID, p.NomeJogador, p.MoedaMumu
        FROM Characters c
        JOIN Players p ON c.PlayerID = p.PlayerID
        ORDER BY p.PlayerID, c.CharID";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Administração de Personagens (AJAX)</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
table { border-collapse: collapse; width: 95%; margin: 20px auto; text-align: center; }
th, td { padding: 8px; border: 1px solid #333; }
th { background: #333; color: #fff; }
input[type="text"], input[type="number"] { width: 90px; }
.btn { padding: 4px 8px; border-radius: 4px; margin: 2px; cursor:pointer; }
.btn-save { background: #4CAF50; color: #fff; }
.btn-delete { background: #ff3333; color: #fff; }
.status-msg { font-size: 0.9em; color: green; }

.hp-container { width: 100px; height: 16px; background: #ccc; border-radius: 4px; margin: auto; }
.hp-bar { height: 100%; border-radius: 4px; background: green; transition: width 0.3s; }
.hp-bar.warn { background: orange; }
.hp-bar.low { background: red; }
</style>
</head>
<body>
<h1>⚡ Administração de Personagens (AJAX Dinâmico)</h1>

<table>
    <tr>
        <th>Jogador</th>
        <th>Personagem</th>
        <th>Classe</th>
        <th>Level</th>
        <th>Exp</th>
        <th>HP</th>
        <th>Moedas</th>
        <th>Arma</th>
        <th>Escudo</th>
        <th>Capacete</th>
        <th>Armadura</th>
        <th>Luva</th>
        <th>Calça</th>
        <th>Ações</th>
    </tr>

<?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
<tr id="row-<?= $row['CharID'] ?>">
    <td><?= $row['NomeJogador'] ?></td>
    <td><input type="text" id="Name-<?= $row['CharID'] ?>" value="<?= $row['Name'] ?>"></td>
    <td><input type="text" id="Class-<?= $row['CharID'] ?>" value="<?= $row['Class'] ?>"></td>
    <td><span id="Level-<?= $row['CharID'] ?>"><?= $row['Level'] ?></span></td>
    <td><span id="Exp-<?= $row['CharID'] ?>"><?= $row['Exp'] ?></span></td>
    <td>
        <div class="hp-container">
            <div class="hp-bar" id="HPBar-<?= $row['CharID'] ?>" style="width: <?= ($row['HP']/$row['MaxHP']*100) ?>%;"></div>
        </div>
        <input type="number" id="HP-<?= $row['CharID'] ?>" value="<?= $row['HP'] ?>" style="width:60px;">
    </td>
    <td><input type="number" id="MoedaMumu-<?= $row['CharID'] ?>" value="<?= $row['MoedaMumu'] ?>"></td>
    <td><input type="text" id="Arma-<?= $row['CharID'] ?>" value="<?= $row['Arma'] ?>"></td>
    <td><input type="text" id="Escudo-<?= $row['CharID'] ?>" value="<?= $row['Escudo'] ?>"></td>
    <td><input type="text" id="Capacete-<?= $row['CharID'] ?>" value="<?= $row['Capacete'] ?>"></td>
    <td><input type="text" id="Armadura-<?= $row['CharID'] ?>" value="<?= $row['Armadura'] ?>"></td>
    <td><input type="text" id="Luva-<?= $row['CharID'] ?>" value="<?= $row['Luva'] ?>"></td>
    <td><input type="text" id="Calça-<?= $row['CharID'] ?>" value="<?= $row['Calça'] ?>"></td>
    <td>
        <button class="btn btn-save" onclick="updateChar(<?= $row['CharID'] ?>)">💾 Salvar</button>
        <button class="btn btn-delete" onclick="deleteChar(<?= $row['CharID'] ?>)">🗑️ Excluir</button>
        <div class="status-msg" id="status-<?= $row['CharID'] ?>"></div>
    </td>
</tr>
<?php endwhile; ?>
</table>

<a href="dashboard.php" class="btn">⬅️ Voltar</a>

<script>
// ===== Atualiza a barra de HP dinamicamente =====
function updateHPBar(CharID, HP, MaxHP){
    let bar = document.getElementById('HPBar-'+CharID);
    let percent = Math.max(0, Math.min(100, Math.round(HP/MaxHP*100)));
    bar.style.width = percent + '%';
    bar.className = 'hp-bar ' + ((percent>60)?'':(percent>30?'warn':'low'));
}

// ===== Atualiza personagem via AJAX =====
function updateChar(CharID){
    const data = {
        CharID: CharID,
        Name: document.getElementById('Name-'+CharID).value,
        Class: document.getElementById('Class-'+CharID).value,
        Level: document.getElementById('Level-'+CharID).innerText,
        Exp: document.getElementById('Exp-'+CharID).innerText,
        HP: document.getElementById('HP-'+CharID).value,
        MoedaMumu: document.getElementById('MoedaMumu-'+CharID).value,
        Arma: document.getElementById('Arma-'+CharID).value,
        Escudo: document.getElementById('Escudo-'+CharID).value,
        Capacete: document.getElementById('Capacete-'+CharID).value,
        Armadura: document.getElementById('Armadura-'+CharID).value,
        Luva: document.getElementById('Luva-'+CharID).value,
        Calça: document.getElementById('Calça-'+CharID).value
    };

    fetch('admin_personagens_update_ajax.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        const status = document.getElementById('status-'+CharID);
        if(res.success){
            status.innerText = '✅ Atualizado!';
            updateHPBar(CharID, data.HP, 100); // MaxHP = 100 por padrão, pode puxar do servidor
            setTimeout(()=>{status.innerText='';},1500);
        } else { status.innerText = '❌ Erro!'; }
    })
    .catch(err => console.error(err));
}

// ===== Deleta personagem via AJAX =====
function deleteChar(CharID){
    if(!confirm('Deseja realmente excluir este personagem?')) return;
    fetch('admin_personagens_delete_ajax.php?CharID='+CharID)
    .then(res => res.json())
    .then(res => {
        if(res.success) document.getElementById('row-'+CharID).remove();
        else alert('Erro ao excluir personagem!');
    })
    .catch(err => console.error(err));
}

// ===== Atualização automática de HP, XP, Level e Moedas =====
function autoUpdateStatus(){
    fetch('status_personagem.php') // precisa retornar JSON com CharID, HP, MaxHP, Level, Exp, MoedaMumu
    .then(res=>res.json())
    .then(data=>{
        data.forEach(char=>{
            if(document.getElementById('HP-'+char.CharID)) {
                document.getElementById('HP-'+char.CharID).value = char.HP;
                document.getElementById('Level-'+char.CharID).innerText = char.Level;
                document.getElementById('Exp-'+char.CharID).innerText = char.Exp;
                document.getElementById('MoedaMumu-'+char.CharID).value = char.MoedaMumu;
                updateHPBar(char.CharID, char.HP, char.MaxHP);
            }
        });
    })
    .catch(err=>console.error(err));
}

// Atualiza a cada 1,5 segundos
setInterval(autoUpdateStatus, 1500);
</script>
</body>
</html>
