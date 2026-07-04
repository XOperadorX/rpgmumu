<?php
include "db.php";

// AJAX handler
if(isset($_POST['ajax']) && $_POST['ajax'] == 1){
    $playerID = (int)$_POST['player_id'];
    $column = $_POST['column'];
    $amount = (int)$_POST['amount'];

    $allowedColumns = ['MoedaMumu','Corrente','Poupanca','Pix','Real','Level','Exp','HP'];
    if(in_array($column, $allowedColumns)){
        $sql = "UPDATE Players SET $column = $column + ? WHERE PlayerID=?";
        sqlsrv_query($conn, $sql, [$amount, $playerID]);

        // Retornar valor atualizado
        $sqlSelect = "SELECT $column FROM Players WHERE PlayerID=?";
        $stmt = sqlsrv_query($conn, $sqlSelect, [$playerID]);
        $val = 0;
        if($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $val = $row[$column];
        }
        echo json_encode(['success'=>true,'value'=>$val]);
    } else {
        echo json_encode(['success'=>false,'error'=>'Coluna inválida']);
    }
    exit;
}

// Puxar lista de jogadores
$players = [];
$sqlPlayers = "SELECT PlayerID, Username, MoedaMumu, Corrente, Poupanca, Pix, Real, Level, Exp, HP FROM Players";
$stmt = sqlsrv_query($conn, $sqlPlayers);
if($stmt !== false){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $players[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Admin - Gerenciamento de Jogadores (AJAX)</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f6f7; margin:0; padding:20px; }
h1 { text-align:center; color:#2c3e50; margin-bottom:20px; }
.dashboard { display:flex; flex-wrap:wrap; justify-content:center; gap:20px; }
.player-card { background:#fff; border:2px solid #ccc; border-radius:10px; padding:15px; width:280px; transition:0.3s; }
.player-card:hover { border-color:#3498db; box-shadow:0 0 10px #3498db; }
.player-card h2 { margin-top:0; color:#2c3e50; text-align:center; }
.player-stats { display:flex; flex-direction:column; gap:8px; margin-bottom:10px; }
.stat { display:flex; justify-content:space-between; align-items:center; }
form.inline { display:flex; gap:5px; justify-content:flex-end; margin-top:5px; }
input[type=number] { width:50px; padding:2px; border-radius:4px; border:1px solid #ccc; }
button.add { padding:4px 8px; background:#3498db; color:#fff; border:none; border-radius:4px; cursor:pointer; transition:0.3s; }
button.add:hover { background:#2980b9; }
@media (max-width:768px){ .player-card{ width:90%; } }
</style>
</head>
<body>

<h1>⚙️ Administração de Jogadores (AJAX)</h1>

<div class="dashboard">
<?php foreach($players as $p): ?>
<div class="player-card" data-playerid="<?= $p['PlayerID'] ?>">
    <h2><?= htmlspecialchars($p['Username']) ?> (ID <?= $p['PlayerID'] ?>)</h2>
    <div class="player-stats">
        <?php foreach(['MoedaMumu','Corrente','Poupanca','Pix','Real','Level','Exp','HP'] as $col): ?>
        <div class="stat">
            <span class="stat-label"><?= $col ?>: <span class="stat-value" data-column="<?= $col ?>"><?= $p[$col] ?></span></span>
            <form class="inline add-form">
                <input type="number" name="amount" value="0">
                <input type="hidden" name="column" value="<?= $col ?>">
                <button type="submit" class="add">➕</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
// Função AJAX para atualizar valor
document.querySelectorAll('.add-form').forEach(form => {
    form.addEventListener('submit', function(e){
        e.preventDefault();
        const parentCard = form.closest('.player-card');
        const playerID = parentCard.getAttribute('data-playerid');
        const column = form.querySelector('[name="column"]').value;
        const amount = parseInt(form.querySelector('[name="amount"]').value);

        if(isNaN(amount) || amount == 0) return;

        const formData = new FormData();
        formData.append('ajax',1);
        formData.append('player_id',playerID);
        formData.append('column',column);
        formData.append('amount',amount);

        fetch('adm.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                parentCard.querySelector('.stat-value[data-column="'+column+'"]').textContent = data.value;
                form.querySelector('[name="amount"]').value = 0;
            } else {
                alert(data.error);
            }
        })
        .catch(err => console.error(err));
    });
});
</script>

</body>
</html>
