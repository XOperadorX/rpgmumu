<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];
$msg = '';

// ===== Puxa saldo atual do Players =====
$sqlPlayer = "SELECT MoedaMumu FROM Players WHERE PlayerID=?";
$stmtPlayer = sqlsrv_query($conn, $sqlPlayer, [$playerID]);
if($stmtPlayer === false) die(print_r(sqlsrv_errors(), true));
$playerData = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC);
$moedaMumu = $playerData['MoedaMumu'] ?? 0;

// ===== Verifica ou cria registro no BankAccounts =====
$sqlCheck = "SELECT * FROM BankAccounts WHERE PlayerID=?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, [$playerID]);
$now = new DateTime();

if(sqlsrv_has_rows($stmtCheck)){
    $bankData = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    // Atualiza Corrente
    sqlsrv_query($conn, "UPDATE BankAccounts SET Corrente=? WHERE PlayerID=?", [$moedaMumu, $playerID]);

    // Atualiza Poupança (5% a cada 6 minutos)
    $lastUpdate = $bankData['LastPoupancaUpdate'] ?? null;
    $poupanca = $bankData['Poupanca'] ?? 0;
    if($lastUpdate){
        $diff = (new DateTime())->getTimestamp() - $lastUpdate->getTimestamp();
        $intervals = floor($diff / 360); // 360s = 6min
        if($intervals > 0){
            $poupanca += $poupanca * 0.05 * $intervals;
            sqlsrv_query($conn, "UPDATE BankAccounts SET Poupanca=?, LastPoupancaUpdate=? WHERE PlayerID=?", [$poupanca, $now, $playerID]);
        }
    } else {
        sqlsrv_query($conn, "UPDATE BankAccounts SET LastPoupancaUpdate=? WHERE PlayerID=?", [$now, $playerID]);
    }

    $bankData['Corrente'] = $moedaMumu;
    $bankData['Poupanca'] = $poupanca;

} else {
    sqlsrv_query($conn, "INSERT INTO BankAccounts (PlayerID, Corrente, Poupanca, Pix, Real, LastPoupancaUpdate) VALUES (?, ?, 0, 0, 0, ?)", [$playerID, $moedaMumu, $now]);
    $bankData = ['Corrente'=>$moedaMumu,'Poupanca'=>0,'Pix'=>0,'Real'=>0];
}

// ===== Transferir Pix para Real =====
if(isset($_POST['transferPix'])){
    $pixAmount = floatval($bankData['Pix']);
    $realAdd = $pixAmount / 2;
    $pixLeft = $pixAmount - $realAdd;
    sqlsrv_query($conn, "UPDATE BankAccounts SET Pix=?, Real=? WHERE PlayerID=?", [$pixLeft, ($bankData['Real']+$realAdd), $playerID]);
    $bankData['Pix'] = $pixLeft;
    $bankData['Real'] += $realAdd;
    $msg = "✅ Pix transferido para Real!";
}

// ===== Transferência para outro jogador =====
if(isset($_POST['transfer'])){
    $targetPlayer = intval($_POST['targetPlayer']);
    $amount = floatval($_POST['amount']);
    $type = $_POST['type']; // Corrente, Poupanca, Pix, Real

    if($targetPlayer == $playerID){
        $msg = "❌ Não é possível transferir para você mesmo!";
    } elseif($amount <= 0){
        $msg = "❌ Valor inválido!";
    } elseif($bankData[$type] < $amount){
        $msg = "❌ Saldo insuficiente em $type!";
    } else {
        // Pega saldo do destinatário
        $stmtTarget = sqlsrv_query($conn, "SELECT * FROM BankAccounts WHERE PlayerID=?", [$targetPlayer]);
        if(sqlsrv_has_rows($stmtTarget)){
            $targetData = sqlsrv_fetch_array($stmtTarget, SQLSRV_FETCH_ASSOC);
            $targetNew = $targetData[$type] + $amount;
            $sourceNew = $bankData[$type] - $amount;

            // Atualiza ambos
            sqlsrv_query($conn, "UPDATE BankAccounts SET $type=? WHERE PlayerID=?", [$sourceNew, $playerID]);
            sqlsrv_query($conn, "UPDATE BankAccounts SET $type=? WHERE PlayerID=?", [$targetNew, $targetPlayer]);

            $bankData[$type] = $sourceNew;
            $msg = "✅ Transferência de $amount $type realizada com sucesso!";
        } else {
            $msg = "❌ Jogador destinatário não encontrado!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>🏦 Banco do Mumu RPG</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
body { font-family: Arial,sans-serif; background:#1c1c1c; color:#fff; text-align:center; padding:30px; }
h1 { color:#ffcc00; }
table { margin:20px auto; border-collapse: collapse; width:60%; }
th, td { padding:10px; border:1px solid #555; }
th { background:#333; }
tr:nth-child(even) { background:#222; }
.btn { display:inline-block; margin:10px; padding:10px 20px; border-radius:5px; text-decoration:none; color:#fff; background:#444; }
.btn:hover { background:#ffcc00; color:#000; }
.btn-red { background:#ff3333; }
form { margin: 20px auto; width: 60%; text-align:left; }
form label, form select, form input { display:block; margin: 5px 0; width:100%; padding:5px; border-radius:5px; border:none; }
form button { margin-top:10px; }
</style>
</head>
<body>

<h1>🏦 Banco do Mumu RPG</h1>

<?php if($msg) echo "<p style='color:lightgreen;'>$msg</p>"; ?>

<table>
<tr>
    <th>Corrente</th>
    <th>Poupança</th>
    <th>Pix</th>
    <th>Real</th>
</tr>
<tr>
    <td><?= number_format($bankData['Corrente'],2) ?></td>
    <td><?= number_format($bankData['Poupanca'],2) ?></td>
    <td><?= number_format($bankData['Pix'],2) ?></td>
    <td><?= number_format($bankData['Real'],2) ?></td>
</tr>
</table>

<form method="post">
    <button type="submit" name="transferPix" class="btn">💸 Transferir Pix para Real</button>
</form>

<h2>💱 Transferência entre jogadores</h2>
<form method="post">
    <label for="targetPlayer">ID do jogador destinatário:</label>
    <input type="number" name="targetPlayer" required>

    <label for="type">Tipo de conta:</label>
    <select name="type">
        <option value="Corrente">Corrente</option>
        <option value="Poupanca">Poupança</option>
        <option value="Pix">Pix</option>
        <option value="Real">Real</option>
    </select>

    <label for="amount">Valor a transferir:</label>
    <input type="number" step="0.01" name="amount" required>

    <button type="submit" name="transfer" class="btn">🔄 Transferir</button>
</form>

<a href="dashboard.php" class="btn">⬅️ Voltar</a>

</body>
</html>
