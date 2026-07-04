<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];
$msg = '';

// ===== 1️⃣ Puxa saldo de moedas =====
$saldoColuna = 'MoedaMumu';
$sqlPlayer = "SELECT $saldoColuna FROM Players WHERE PlayerID=?";
$stmtPlayer = sqlsrv_query($conn, $sqlPlayer, [$playerID]);

if($stmtPlayer === false){
    echo "<pre>Erro SQL Players:\n";
    print_r(sqlsrv_errors());
    die();
}

$playerData = sqlsrv_fetch_array($stmtPlayer, SQLSRV_FETCH_ASSOC);
$moedaMumu = $playerData[$saldoColuna] ?? 0;

// ===== 2️⃣ Puxa dados da conta bancária =====
$sqlBank = "SELECT Corrente, Poupanca, Pix, Real, LastUpdate FROM BankAccounts WHERE PlayerID=?";
$stmtBank = sqlsrv_query($conn, $sqlBank, [$playerID]);

if($stmtBank === false){
    echo "<pre>Erro SQL BankAccounts:\n";
    print_r(sqlsrv_errors());
    die();
}

$rowBank = sqlsrv_fetch_array($stmtBank, SQLSRV_FETCH_ASSOC);

// Se não existir registro, cria
if(!$rowBank){
    $insertBank = "INSERT INTO BankAccounts (PlayerID, Corrente, Poupanca, Pix, Real, LastUpdate) 
                   VALUES (?, 0, 0, 0, 0, GETDATE())";
    $resInsert = sqlsrv_query($conn, $insertBank, [$playerID]);
    if($resInsert === false){
        echo "<pre>Erro ao criar BankAccounts:\n";
        print_r(sqlsrv_errors());
        die();
    }
    $rowBank = [
        'Corrente' => 0,
        'Poupanca' => 0,
        'Pix'      => 0,
        'Real'     => 0,
        'LastUpdate' => new DateTime()
    ];
}

// ===== 3️⃣ Atualiza Corrente com MoedaMumu =====
sqlsrv_query($conn, "UPDATE BankAccounts SET Corrente=? WHERE PlayerID=?", [$moedaMumu, $playerID]);

// ===== 4️⃣ Atualização somente via botão =====
if(isset($_POST['atualizar'])){
    $rowBank = sqlsrv_fetch_array(sqlsrv_query($conn, $sqlBank, [$playerID]), SQLSRV_FETCH_ASSOC);
    
    $contaCorrente = $rowBank['Corrente'] ?? 0;
    $poupanca      = $rowBank['Poupanca'] ?? 0;
    $pix           = $rowBank['Pix'] ?? 0;
    $real          = $rowBank['Real'] ?? 0;
    $lastUpdate    = $rowBank['LastUpdate'] ?? new DateTime();

    if(!($lastUpdate instanceof DateTime)){
        $lastUpdate = new DateTime();
    }

    $agora = new DateTime();
    $diff = $agora->getTimestamp() - $lastUpdate->getTimestamp();
    $cycles = floor($diff / 360); // cada ciclo = 6 min

    if($cycles > 0 && $poupanca > 0){
        $rendimento = $poupanca * (0.0001 * $cycles); // 0,01% por ciclo
        $poupanca += $rendimento;

        sqlsrv_query($conn, "UPDATE BankAccounts SET Poupanca=?, LastUpdate=GETDATE() WHERE PlayerID=?", [$poupanca, $playerID]);
        $msg = "💹 Rendimento aplicado: +".number_format($rendimento,2,",",".");
    }

    // Atualiza array para exibir no HTML
    $account = [
        'PlayerID'  => $playerID,
        'MoedaMumu' => $moedaMumu,
        'Corrente'  => $contaCorrente,
        'Poupanca'  => $poupanca,
        'Pix'       => $pix,
        'Real'      => $real
    ];
} else {
    $account = [
        'PlayerID'  => $playerID,
        'MoedaMumu' => $moedaMumu,
        'Corrente'  => $rowBank['Corrente'] ?? 0,
        'Poupanca'  => $rowBank['Poupanca'] ?? 0,
        'Pix'       => $rowBank['Pix'] ?? 0,
        'Real'      => $rowBank['Real'] ?? 0
    ];
}

// ===== 5️⃣ Histórico =====
$historyStmt = sqlsrv_query($conn, "SELECT TOP 10 Tipo, Valor, Data FROM BankHistory WHERE PlayerID=? ORDER BY Data DESC", [$playerID]);
$history = [];
if($historyStmt !== false){
    while($rowHist = sqlsrv_fetch_array($historyStmt, SQLSRV_FETCH_ASSOC)){
        $history[] = $rowHist;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Banco Mumu RPG</title>
<style>
body { background:#1c1c1c; color:#fff; font-family:Arial,sans-serif; text-align:center; }
.card { background:#2b2b2b; padding:20px; border-radius:10px; margin:20px auto; width:420px; box-shadow:0 0 10px #000; }
input[type=number] { width:80px; padding:5px; border-radius:5px; border:none; text-align:center; }
button { padding:10px 15px; border-radius:5px; border:none; cursor:pointer; margin:5px; background:#ffcc00; color:#000; font-weight:bold; }
button:hover { background:#ffdd33; }
h1 { color:#ffcc00; display:flex; justify-content: space-between; align-items:center; }
table { width:90%; margin:20px auto; border-collapse:collapse; text-align:center; }
th, td { border:1px solid #555; padding:8px; }
th { background:#333; color:#ffcc00; }
.msg { color: #00ff00; font-weight:bold; }
.update-btn { background:#00ccff; color:#000; padding:5px 10px; border-radius:5px; font-weight:bold; text-decoration:none; }
.update-btn:hover { background:#33ddff; }
</style>
</head>
<body>

<h1>
🏦 Banco Mumu RPG
<form method="post" style="display:inline;">
<button type="submit" name="atualizar" class="update-btn">🔄 Atualizar</button>
</form>
</h1>

<?php if($msg): ?><p class="msg"><?= $msg ?></p><?php endif; ?>

<table>
<tr>
<th>💰 Moedas Mumu</th>
<th>🏦 Corrente</th>
<th>💹 Poupança</th>
<th>📲 Pix</th>
<th>💵 Real</th>
</tr>
<tr>
<td><?= number_format($account['MoedaMumu'],2) ?></td>
<td><?= number_format($account['Corrente'],2) ?></td>
<td><?= number_format($account['Poupanca'],2) ?></td>
<td><?= number_format($account['Pix'],2) ?></td>
<td><?= number_format($account['Real'],2) ?></td>
</tr>
</table>

<div class="card">
<h2>Conta de <?= $account['PlayerID'] ?></h2>

<form method="post">
<label>Mumu → Corrente:</label><br>
<input type="number" name="deposit" min="1" max="<?= $account['MoedaMumu'] ?>" required>
<button type="submit" name="mumuToCorrente">Transferir</button>
</form>

<form method="post">
<label>Corrente → Poupança:</label><br>
<input type="number" name="deposit" min="1" max="<?= $account['Corrente'] ?>" required>
<button type="submit" name="correnteToPoupanca">Transferir</button>
</form>

<form method="post">
<label>Poupança → Pix:</label><br>
<input type="number" name="deposit" min="1" max="<?= $account['Poupanca'] ?>" required>
<button type="submit" name="poupancaToPix">Transferir</button>
</form>

<form method="post">
<label>Pix → Real:</label><br>
<input type="number" name="pixAmount" min="1" max="<?= $account['Pix'] ?>" required>
<button type="submit" name="pixToReal">Converter</button>
</form>
</div>

<h2>📜 Histórico (últimos 10)</h2>
<table>
<tr><th>Tipo</th><th>Valor</th><th>Data</th></tr>
<?php if(!empty($history)): ?>
<?php foreach($history as $h): ?>
<tr>
<td><?= htmlspecialchars($h['Tipo'] ?? '') ?></td>
<td><?= htmlspecialchars($h['Valor'] ?? '') ?></td>
<td><?= htmlspecialchars($h['Data'] ?? '-') ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="3">Nenhum histórico encontrado.</td></tr>
<?php endif; ?>
</table>

</body>
</html>
