<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];


// Pega dados do jogador e conta bancária
$stmt = sqlsrv_query($conn, "SELECT p.PlayerID, p.MoedaMumu, b.Corrente, b.Poupanca, b.Pix, b.Real, b.LastInterest
                             FROM Players p
                             LEFT JOIN BankAccounts b ON p.PlayerID = b.PlayerID
                             WHERE p.PlayerID = ?", [$playerID]);

if(!$stmt || !sqlsrv_has_rows($stmt)){
    die("Conta não encontrada!");
}


// Pega saldo de moedas
$stmtMoedas = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID=?", [$playerID]);
$moedaRow = sqlsrv_fetch_array($stmtMoedas, SQLSRV_FETCH_ASSOC);
$moedas = $moedaRow['MoedaMumu'] ?? 0;


// ===== 1️⃣ Puxa saldo atual do Players =====
$sqlPlayer = "SELECT MoedaMumu FROM Players WHERE PlayerID=?";
$stmtPlayer = sqlsrv_query($conn, $sqlPlayer, [$playerID]);
if($stmtPlayer === false) die(print_r(sqlsrv_errors(), true));

$playerData = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC);
$moedaMumu = $playerData['MoedaMumu'] ?? 0;

// ===== 2️⃣ Verifica se já existe registro no BankAccounts =====
$sqlCheck = "SELECT * FROM BankAccounts WHERE PlayerID=?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, [$playerID]);
$now = new DateTime();

if(sqlsrv_has_rows($stmtCheck)){
    $bankData = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);

    // Atualiza Corrente com MoedaMumu atual
    $sqlUpdate = "UPDATE BankAccounts SET Corrente=? WHERE PlayerID=?";
    sqlsrv_query($conn, $sqlUpdate, [$moedaMumu, $playerID]);

    // Aplica rendimento da Poupança a cada 6 minutos
    $lastUpdate = $bankData['LastPoupancaUpdate'] ?? null;
    $poupanca = $bankData['Poupanca'] ?? 0;
    if($lastUpdate){
        $lastTime = $lastUpdate;
        $diff = (new DateTime())->getTimestamp() - $lastTime->getTimestamp();
        $intervals = floor($diff / 360); // 360s = 6min
        if($intervals > 0){
            $poupanca += $poupanca * 0.05 * $intervals;
            $sqlPoupanca = "UPDATE BankAccounts SET Poupanca=?, LastPoupancaUpdate=? WHERE PlayerID=?";
            sqlsrv_query($conn, $sqlPoupanca, [$poupanca, $now, $playerID]);
        }
    } else {
        $sqlInit = "UPDATE BankAccounts SET LastPoupancaUpdate=? WHERE PlayerID=?";
        sqlsrv_query($conn, $sqlInit, [$now, $playerID]);
    }

    $bankData['Corrente'] = $moedaMumu;
    $bankData['Poupanca'] = $poupanca;
}else{
    // Cria novo registro
    $sqlInsert = "INSERT INTO BankAccounts (PlayerID, Corrente, Poupanca, Pix, Real, LastPoupancaUpdate) VALUES (?, ?, 0, 0, 0, ?)";
    sqlsrv_query($conn, $sqlInsert, [$playerID, $moedaMumu, $now]);

    $bankData = [
        'Corrente' => $moedaMumu,
        'Poupanca' => 0,
        'Pix' => 0,
        'Real' => 0
    ];
}

// ===== 3️⃣ Transferir Pix para Real (opcional) =====
if(isset($_POST['transferPix'])){
    $pixAmount = floatval($bankData['Pix']);
    $realAdd = $pixAmount / 2;
    $pixLeft = $pixAmount - $realAdd;
    $sqlPix = "UPDATE BankAccounts SET Pix=?, Real=? WHERE PlayerID=?";
    sqlsrv_query($conn, $sqlPix, [$pixLeft, ($bankData['Real']+$realAdd), $playerID]);

    $bankData['Pix'] = $pixLeft;
    $bankData['Real'] += $realAdd;
    $msg = "✅ Pix transferido para Real!";
}

// Pega histórico
$historyStmt = sqlsrv_query($conn, "SELECT TOP 10 Tipo, Valor, Data FROM BankHistory WHERE PlayerID=? ORDER BY Data DESC", [$playerID]);
$history = [];
while($row = sqlsrv_fetch_array($historyStmt, SQLSRV_FETCH_ASSOC)){
    $history[] = $row;
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
</style>
</head>
<body>

<h1>🏦 Banco do Mumu RPG</h1>
<h2>💰 Moedas Mumu: <span id="moedas"><?= $moedas ?></span></h2>

<?php if(isset($msg)) echo "<p style='color:lightgreen;'>$msg</p>"; ?>

<table>
<tr>
    <th>💰 Moedas Mumu:</th>
    <th>🏦 Corrente:</th>
    <th>💹 Poupança:</th>
    <th>📲 Pix:</th>
    <th>💵 Real:</th>
</tr>
<tr>
    <td><span id="moedas"><?= $moedas ?></span></td>
    <td><?= number_format($bankData['Corrente'],2) ?></td>
    <td><?= number_format($bankData['Poupanca'],2) ?></td>
    <td><?= number_format($bankData['Pix'],2) ?></td>
    <td><?= number_format($bankData['Real'],2) ?></td>
	
</tr>
</table>

<form method="post">
    <button type="submit" name="transferPix" class="btn">💸 Transferir 📲 Pix para 💵 Real</button>
</form>

<a href="dashboard.php" class="btn">⬅️ Voltar</a>

</body>
</html>
