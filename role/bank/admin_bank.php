<?php
session_start();
include "../../db.php"; // Ajuste conforme sua estrutura

// ===== 1️⃣ Verifica login e admin =====
if (!isset($_SESSION['PlayerID'])) die("⛔ Acesso negado.");
$playerID = $_SESSION['PlayerID'];

$stmt = sqlsrv_query($conn, "SELECT Role, Username FROM Players WHERE PlayerID=?", [$playerID]);
if ($stmt === false) die("Erro: " . print_r(sqlsrv_errors(), true));
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row || $row['Role'] !== 'admin') die("⛔ Acesso negado.");

$adminName = $row['Username'];

// ===== 2️⃣ Busca contas bancárias =====
$sqlBank = "SELECT B.AccountID, B.PlayerID, P.Username,
            B.Corrente, B.Poupanca, B.Pix, B.Real, B.LastUpdate
            FROM BankAccounts B
            JOIN Players P ON B.PlayerID = P.PlayerID
            ORDER BY B.LastUpdate DESC";
$stmtBank = sqlsrv_query($conn, $sqlBank);
if ($stmtBank === false) die("Erro na consulta de contas: " . print_r(sqlsrv_errors(), true));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Admin - Banco</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{background:#1c1c1c;color:#fff;font-family:Arial;margin:0;}
header{background:#0077cc;padding:20px;text-align:center;}
nav{background:#222;padding:15px;text-align:center;}
nav a{margin:0 10px;padding:12px 20px;border-radius:5px;background:#444;color:#fff;text-decoration:none;font-weight:bold;display:inline-block;transition:0.3s;}
nav a:hover{background:#666;}
main{padding:20px;overflow-x:auto;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th, td{padding:10px;border:1px solid #444;text-align:center;}
th{background:#0077cc;}
tr:nth-child(even){background:#2a2a2a;}
button{padding:5px 10px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;}
.btn-del{background:#cc0000;color:#fff;}
.btn-del:hover{background:#ff3333;}
.msg{color:orange;margin-top:10px;text-align:center;}
input.edit-balance{width:80px;text-align:right;}
@media(max-width:600px){th, td{font-size:12px;padding:5px;}nav a{padding:8px 12px;margin:5px;}}
</style>
</head>
<body>
<header>
<h1>🏦 Administração de Banco</h1>
<p>Admin: <?= htmlspecialchars($adminName) ?></p>
</header>
<nav>
  <a href="../admin_dashboard.php">⬅ Voltar</a>
  <a href="../logout.php">🚪 Sair</a>
</nav>
<main>
<div id="msg" class="msg"></div>
<table>
<tr>
<th>ID Conta</th>
<th>Jogador</th>
<th>Corrente</th>
<th>Poupança</th>
<th>Pix</th>
<th>Real</th>
<th>Última Atualização</th>
<th>Ações</th>
</tr>
<?php while($acc = sqlsrv_fetch_array($stmtBank, SQLSRV_FETCH_ASSOC)):
    $lastUpdate = ($acc['LastUpdate'] instanceof DateTime) ? $acc['LastUpdate']->format("d/m/Y H:i") : '—';
?>
<tr>
<td><?= $acc['AccountID'] ?></td>
<td><?= htmlspecialchars($acc['Username']) ?> (ID: <?= $acc['PlayerID'] ?>)</td>
<td><input type="number" class="edit-balance" data-accountid="<?= $acc['AccountID'] ?>" data-field="Corrente" value="<?= number_format($acc['Corrente'],2,'.','') ?>"></td>
<td><input type="number" class="edit-balance" data-accountid="<?= $acc['AccountID'] ?>" data-field="Poupanca" value="<?= number_format($acc['Poupanca'],2,'.','') ?>"></td>
<td><input type="number" class="edit-balance" data-accountid="<?= $acc['AccountID'] ?>" data-field="Pix" value="<?= number_format($acc['Pix'],2,'.','') ?>"></td>
<td><input type="number" class="edit-balance" data-accountid="<?= $acc['AccountID'] ?>" data-field="Real" value="<?= number_format($acc['Real'],2,'.','') ?>"></td>
<td><?= $lastUpdate ?></td>
<td><button class="btn-del" data-accountid="<?= $acc['AccountID'] ?>">🗑 Excluir</button></td>
</tr>
<?php endwhile; ?>
</table>
</main>

<script>
// ===== Funções AJAX =====
function attachEvents() {
    // Delete
    document.querySelectorAll('.btn-del').forEach(btn => {
        btn.addEventListener('click', function(){
            if(!confirm('⚠ Tem certeza que deseja excluir esta conta?')) return;
            const accountID = this.dataset.accountid;
            fetch('admin_bank_delete.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'AccountID='+accountID
            })
            .then(resp => resp.json())
            .then(data => {
                document.getElementById('msg').innerText = data.message;
                setTimeout(()=>location.reload(),500);
            });
        });
    });

    // Edit balance
    document.querySelectorAll('.edit-balance').forEach(input => {
        input.addEventListener('change', function(){
            const accountID = this.dataset.accountid;
            const field = this.dataset.field;
            const newBalance = this.value;
            fetch('admin_bank_edit_ajax.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'AccountID='+accountID+'&Field='+field+'&Balance='+newBalance
            })
            .then(resp => resp.json())
            .then(data => {
                document.getElementById('msg').innerText = data.message;
                setTimeout(()=>location.reload(),500);
            });
        });
    });
}

// Inicial
attachEvents();
</script>
</body>
</html>
