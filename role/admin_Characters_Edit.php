<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("⛔ Acesso negado. Faça login.");
}

// ===== Verifica se o jogador é admin =====
$playerID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID=?", [$playerID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role'] !== 'admin') die("⛔ Acesso negado. Apenas admins.");

$taxaExclusao = 0;

// ===== AJAX Actions =====
if(isset($_POST['action'])){
    $action = $_POST['action'];

    switch($action){
        case 'delete':
            if(!isset($_POST['CharID'])) exit;
            $charID = intval($_POST['CharID']);
            handleDelete($conn, $charID, $taxaExclusao);
            break;
        case 'edit':
            if(!isset($_POST['CharID'], $_POST['Name'], $_POST['Class'])) exit;
            $charID = intval($_POST['CharID']);
            $name = $_POST['Name'];
            $class = $_POST['Class'];
            handleEdit($conn, $charID, $name, $class);
            break;
        case 'add':
            if(!isset($_POST['Name'], $_POST['Class'])) exit;
            $name = $_POST['Name'];
            $class = $_POST['Class'];
            handleAdd($conn, $name, $class);
            break;
        case 'upgrade':
            if(!isset($_POST['CharID'], $_POST['Stat'])) exit;
            $charID = intval($_POST['CharID']);
            $stat = $_POST['Stat'];
            handleUpgrade($conn, $charID, $stat);
            break;
    }
    exit;
}

// ===== Funções =====
function handleDelete($conn, $charID, $taxaExclusao){
    sqlsrv_begin_transaction($conn);
    try {
        sqlsrv_query($conn, "DELETE FROM Items WHERE CharID=?", [$charID]);
        sqlsrv_query($conn, "DELETE FROM Historico WHERE CharID=?", [$charID]);
        sqlsrv_query($conn, "DELETE FROM Characters WHERE CharID=?", [$charID]);
        sqlsrv_commit($conn);
        echo json_encode(['status'=>'success','msg'=>"✅ Personagem excluído!"]);
    } catch(Exception $e){
        sqlsrv_rollback($conn);
        echo json_encode(['status'=>'error','msg'=>"❌ Erro: ".$e->getMessage()]);
    }
}

function handleEdit($conn, $charID, $name, $class){
    $sql = "UPDATE Characters SET Name=?, Class=? WHERE CharID=?";
    $stmt = sqlsrv_query($conn, $sql, [$name, $class, $charID]);
    echo json_encode(['status'=> $stmt ? 'success' : 'error', 'msg'=> $stmt ? "✅ Personagem atualizado!" : "❌ Falha ao atualizar."]);
}

function handleAdd($conn, $name, $class){
    $sql = "INSERT INTO Characters (Name, Class, Level, Exp, HP, MaxHP, Mana, MaxMana, Power, MaxPower)
            VALUES (?, ?, 1, 0, 100, 100, 50, 50, 10, 10)";
    $stmt = sqlsrv_query($conn, $sql, [$name, $class]);
    echo json_encode(['status'=> $stmt ? 'success' : 'error', 'msg'=> $stmt ? "✅ Personagem adicionado!" : "❌ Falha ao adicionar."]);
}

function handleUpgrade($conn, $charID, $stat){
    $valid = ['HP','Mana','Power'];
    if(!in_array($stat, $valid)) exit;
    $sql = "UPDATE Characters SET {$stat} = {$stat}+10, Max{$stat} = Max{$stat}+10 WHERE CharID=?";
    $stmt = sqlsrv_query($conn, $sql, [$charID]);
    echo json_encode(['status'=> $stmt ? 'success' : 'error', 'msg'=> $stmt ? "✅ {$stat} +10 aplicado!" : "❌ Falha no upgrade."]);
}

// ===== Renderizar todos os personagens =====
function renderCharacters($conn, $taxaExclusao){
    $sql = "SELECT * FROM Characters";
    $stmt = sqlsrv_query($conn, $sql);
    if(!$stmt || !sqlsrv_has_rows($stmt)) return "<p>Nenhum personagem encontrado.</p>";

    $html = "";
    while($char = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $hpPercent   = ($char['MaxHP']>0)?($char['HP']/$char['MaxHP']*100):0;
        $manaPercent = ($char['MaxMana']>0)?($char['Mana']/$char['MaxMana']*100):0;
        $powPercent  = ($char['MaxPower']>0)?($char['Power']/$char['MaxPower']*100):0;
        $expPercent  = ($char['Level']>0)?($char['Exp']/($char['Level']*100)*100):0;

        $html .= "<div class='char-card' data-id='{$char['CharID']}'>
                    <input class='edit-name' value='{$char['Name']}' />
                    <input class='edit-class' value='{$char['Class']}' />
                    <strong>Level: {$char['Level']}</strong>
                    <div class='bar exp-bar' style='width:{$expPercent}%;'>EXP: {$char['Exp']}/".($char['Level']*100)."</div>
                    <div class='bar'><div class='hp-bar' style='width:{$hpPercent}%;'>HP: {$char['HP']}/{$char['MaxHP']}</div></div>
                    <div class='bar'><div class='mana-bar' style='width:{$manaPercent}%;'>Mana: {$char['Mana']}/{$char['MaxMana']}</div></div>
                    <div class='bar'><div class='power-bar' style='width:{$powPercent}%;'>Power: {$char['Power']}/{$char['MaxPower']}</div></div>
                    <button class='btn edit-btn'>💾 Salvar</button>
                    <button class='btn upgrade-hp'>❤️ HP +10 -</button>
                    <button class='btn upgrade-mana'>🔵 Mana +10 -</button>
                    <button class='btn upgrade-power'>⚡ Power +10 -</button>
                    <button class='btn delete-btn'>🗑️ Excluir</button>
                  </div>";
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Admin - Todos os Personagens</title>
<style>
body { font-family: 'Orbitron', sans-serif; background: radial-gradient(circle,#0a0a0f 60%,#020205); color:#0ff; text-align:center; margin:0; padding:0; }
.char-card { background:rgba(0,255,255,0.1); border:1px solid #0ff; border-radius:12px; padding:15px; margin:10px auto; width:360px; box-shadow:0 0 15px #0ff; transition:0.3s; }
.char-card:hover { box-shadow:0 0 25px #00ffff; transform:scale(1.02);}
.bar { background:#222; border-radius:5px; margin:5px 0; height:20px; overflow:hidden; font-size:12px; line-height:20px; }
.hp-bar { background:red; height:100%; color:#fff; text-align:center; transition:0.5s; }
.mana-bar { background:blue; height:100%; color:#fff; text-align:center; transition:0.5s; }
.power-bar { background:gold; height:100%; color:#000; text-align:center; transition:0.5s; }
.exp-bar { background:purple; color:#fff; text-align:center; transition:0.5s; }
.btn { background:linear-gradient(90deg,#00ffff,#0077ff); border:none; color:#000; padding:6px 12px; border-radius:8px; cursor:pointer; margin:3px; font-weight:bold; }
.btn:hover { background:linear-gradient(90deg,#00ccff,#0055ff); transform:scale(1.05); }
.edit-name, .edit-class { width:90%; margin:5px 0; padding:5px; border-radius:5px; border:none; text-align:center; }
.mensagem { margin-top:15px; font-weight:bold; }
.add-form input { padding:5px; margin:5px; border-radius:5px; border:none; }
.add-form button { margin-top:5px; }
nav { background:#222; padding:15px; text-align:center; }
nav a { margin:0 10px; padding:12px 20px; border-radius:5px; background:#444; color:#fff; text-decoration:none; font-weight:bold; display:inline-block; transition:0.3s; }
nav a:hover { background:#666; }
</style>
</head>
<body>
<nav>
<a href="admin_dashboard.php">⬅ Voltar ao Painel</a>
<a href="logout.php">🚪 Sair</a>
</nav>
<h2>🛡️ Admin - Todos os Personagens</h2>

<div class="add-form">
    <input id="new-name" placeholder="Nome do Personagem" />
    <input id="new-class" placeholder="Classe" />
    <button id="add-btn" class="btn">➕ Adicionar Personagem</button>
</div>

<div id="characters-list">
    <?= renderCharacters($conn, $taxaExclusao) ?>
</div>

<div class="mensagem" id="mensagem"></div>

<script>
document.addEventListener('DOMContentLoaded', ()=>{

    function showMessage(msg){ document.getElementById('mensagem').innerHTML = msg; }

    function ajaxAction(action, data, callback){
        data.append('action', action);
        fetch('', {method:'POST', body:data})
            .then(res=>res.json())
            .then(resp=>callback(resp));
    }

    function attachButtons(){
        document.querySelectorAll('.delete-btn').forEach(btn=>{
            btn.onclick = ()=>{
                if(confirm('⚠️ Deseja realmente excluir este personagem?')){
                    let charID = btn.closest('.char-card').dataset.id;
                    let data = new FormData();
                    data.append('CharID', charID);
                    ajaxAction('delete', data, resp=>{
                        showMessage(resp.msg);
                        if(resp.status==='success') btn.closest('.char-card').remove();
                    });
                }
            };
        });

        document.querySelectorAll('.edit-btn').forEach(btn=>{
            btn.onclick = ()=>{
                let card = btn.closest('.char-card');
                let charID = card.dataset.id;
                let data = new FormData();
                data.append('CharID', charID);
                data.append('Name', card.querySelector('.edit-name').value);
                data.append('Class', card.querySelector('.edit-class').value);
                ajaxAction('edit', data, resp=>showMessage(resp.msg));
            };
        });

        document.querySelectorAll('.upgrade-hp').forEach(btn=>{
            btn.onclick = ()=>upgradeStat(btn, 'HP');
        });
        document.querySelectorAll('.upgrade-mana').forEach(btn=>{
            btn.onclick = ()=>upgradeStat(btn, 'Mana');
        });
        document.querySelectorAll('.upgrade-power').forEach(btn=>{
            btn.onclick = ()=>upgradeStat(btn, 'Power');
        });
    }

    function upgradeStat(btn, stat){
        let charID = btn.closest('.char-card').dataset.id;
        let data = new FormData();
        data.append('CharID', charID);
        data.append('Stat', stat);
        ajaxAction('upgrade', data, resp=>{
            showMessage(resp.msg);
            if(resp.status==='success') location.reload(); // Atualiza barras após upgrade
        });
    }

    attachButtons();

    document.getElementById('add-btn').onclick = ()=>{
        let name = document.getElementById('new-name').value;
        let cls  = document.getElementById('new-class').value;
        if(!name || !cls){ showMessage("❌ Preencha todos os campos."); return; }
        let data = new FormData();
        data.append('Name', name);
        data.append('Class', cls);
        ajaxAction('add', data, resp=>{
            showMessage(resp.msg);
            if(resp.status==='success') location.reload();
        });
    };
});
</script>

</body>
</html>
