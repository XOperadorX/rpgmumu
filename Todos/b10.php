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

// ===== 2️⃣ Puxa ou cria dados da conta bancária usando Upsert =====
$sqlBank = "
IF EXISTS(SELECT 1 FROM BankAccounts WHERE PlayerID = ?)
    SELECT Corrente, Poupanca, Pix, Real FROM BankAccounts WHERE PlayerID = ?
ELSE
BEGIN
    INSERT INTO BankAccounts (PlayerID, Corrente, Poupanca, Pix, Real, LastUpdate)
    VALUES (?, 0, 0, 0, 0, GETDATE());
    SELECT Corrente=0, Poupanca=0, Pix=0, Real=0;
END
";

$stmtBank = sqlsrv_query($conn, $sqlBank, [$playerID, $playerID, $playerID]);

if($stmtBank === false){
    echo "<pre>Erro SQL BankAccounts:\n";
    print_r(sqlsrv_errors());
    die();
}

$rowBank = sqlsrv_fetch_array($stmtBank, SQLSRV_FETCH_ASSOC);

// ===== 3️⃣ Lógica dos botões de transferência =====
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $corrente = $rowBank['Corrente'] ?? 0;
    $poupanca = $rowBank['Poupanca'] ?? 0;
    $pix = $rowBank['Pix'] ?? 0;
    $real = $rowBank['Real'] ?? 0;

    $updateBank = false;

    // Função para registrar histórico
    function addHistory($conn, $playerID, $tipo, $valor){
        $sql = "INSERT INTO BankHistory (PlayerID, Tipo, Valor, Data) VALUES (?, ?, ?, GETDATE())";
        sqlsrv_query($conn, $sql, [$playerID, $tipo, $valor]);
    }

    // Mumu → Corrente
    if(isset($_POST['mumuToCorrente'])){
        $amount = floatval($_POST['deposit']);
        if($amount <= 0){
            $msg = "❌ Valor inválido!";
        } elseif($amount > $moedaMumu){
            $msg = "❌ Saldo insuficiente de MoedaMumu!";
        } else {
            $corrente += $amount;
            $moedaMumu -= $amount;
            sqlsrv_query($conn, "UPDATE Players SET MoedaMumu=? WHERE PlayerID=?", [$moedaMumu, $playerID]);
            $updateBank = true;
            addHistory($conn, $playerID, "Mumu → Corrente", $amount);
            $msg = "✅ Transferência Mumu → Corrente realizada!";
        }
    }

    // Corrente → Poupança
    if(isset($_POST['correnteToPoupanca'])){
        $amount = floatval($_POST['deposit']);
        if($amount <= 0){
            $msg = "❌ Valor inválido!";
        } elseif($amount > $corrente){
            $msg = "❌ Saldo insuficiente na Corrente!";
        } else {
            $corrente -= $amount;
            $poupanca += $amount;
            $updateBank = true;
            addHistory($conn, $playerID, "Corrente → Poupança", $amount);
            $msg = "✅ Transferência Corrente → Poupança realizada!";
        }
    }

    // Poupança → Pix
    if(isset($_POST['poupancaToPix'])){
        $amount = floatval($_POST['deposit']);
        if($amount <= 0){
            $msg = "❌ Valor inválido!";
        } elseif($amount > $poupanca){
            $msg = "❌ Saldo insuficiente na Poupança!";
        } else {
            $poupanca -= $amount;
            $pix += $amount;
            $updateBank = true;
            addHistory($conn, $playerID, "Poupança → Pix", $amount);
            $msg = "✅ Transferência Poupança → Pix realizada!";
        }
    }

    // Pix → Real
    if(isset($_POST['pixToReal'])){
        $amount = floatval($_POST['pixAmount']);
        if($amount <= 0){
            $msg = "❌ Valor inválido!";
        } elseif($amount > $pix){
            $msg = "❌ Saldo insuficiente no Pix!";
        } else {
            $pix -= $amount;
            $real += $amount;
            $updateBank = true;
            addHistory($conn, $playerID, "Pix → Real", $amount);
            $msg = "✅ Conversão Pix → Real realizada!";
        }
    }

    // Atualiza BankAccounts se necessário
    if($updateBank){
        $sqlUpdateBank = "
        UPDATE BankAccounts
        SET Corrente = ?, Poupanca = ?, Pix = ?, Real = ?, LastUpdate = GETDATE()
        WHERE PlayerID = ?";
        sqlsrv_query($conn, $sqlUpdateBank, [$corrente, $poupanca, $pix, $real, $playerID]);
    }

    $account = [
        'PlayerID'  => $playerID,
        'MoedaMumu' => $moedaMumu,
        'Corrente'  => $corrente,
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

// ===== 4️⃣ Histórico =====
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
button { padding:10px 15px; border-radius:5px; border:none; cursor:pointer; margin:5px; background:#ffcc00; color:#000; font-weight:bold; transition:0.2s; }
button:hover { background:#ffdd33; }
h1 { color:#ffcc00; }
table { width:90%; margin:20px auto; border-collapse:collapse; text-align:center; }
th, td { border:1px solid #555; padding:8px; }
th { background:#333; color:#ffcc00; }
.msg { color: #00ff00; font-weight:bold; }
.update-btn { background:#00ccff; color:#000; padding:5px 10px; border-radius:5px; font-weight:bold; text-decoration:none; }
.update-btn:hover { background:#33ddff; }
.btn { display:inline-block; margin:10px; padding:10px 20px; border-radius:5px; text-decoration:none; color:#fff; background:#444; }
.btn:hover { background:#ffcc00; color:#000; }
</style>
</head>
<body>

<h1>🏦 Banco Mumu RPG</h1>

<div style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <h1>🏦 Banco Mumu RPG</h1>
    <form method="post" style="margin:0;">
        <button type="submit"class="btn" name="refresh">🔄 Atualizar</button>
		<a href="dashboard.php" class="btn">⬅️ Voltar</a>

    </form>
</div>


<?php if($msg): ?><p class="msg"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

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
<h2>Conta de <?= htmlspecialchars($account['PlayerID']) ?></h2>
<hr>

<!-- Mumu → Corrente -->
<form method="post">
<label>Mumu → Corrente:</label><br>
<input type="number" name="deposit" min="1" max="<?= intval($account['MoedaMumu']) ?>" required>
<button type="submit" name="mumuToCorrente">Transferir</button>
</form>

<!-- Corrente → Poupança -->
<form method="post">
<label>Corrente → Poupança:</label><br>
<input type="number" name="deposit" min="1" max="<?= intval($account['Corrente']) ?>" required>
<button type="submit" name="correnteToPoupanca">Transferir</button>
</form>

<!-- Poupança → Pix -->
<form method="post">
<label>Poupança → Pix:</label><br>
<input type="number" name="deposit" min="1" max="<?= intval($account['Poupanca']) ?>" required>
<button type="submit" name="poupancaToPix">Transferir</button>
</form>

<!-- Pix → Real -->
<form method="post">
<label>Pix → Real:</label><br>
<input type="number" name="pixAmount" min="1" max="<?= intval($account['Pix']) ?>" required>
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
<td><?= htmlspecialchars(number_format($h['Valor'],2) ?? '') ?></td>
<td>
<?php
if(isset($h['Data']) && $h['Data'] instanceof DateTime){
    echo $h['Data']->format('d/m/Y H:i');
} elseif(!empty($h['Data'])){
    echo htmlspecialchars($h['Data']);
} else {
    echo '-';
}
?>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="3">Nenhum histórico encontrado.</td></tr>
<?php endif; ?>
</table>

</body>
</html>
