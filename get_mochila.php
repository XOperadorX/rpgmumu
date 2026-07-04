<?php
session_start();
include "db.php";
include "check_ban.php";

if(!isset($_SESSION['PlayerID'])){
    die("⛔ Acesso negado. Faça login primeiro.");
}

$playerID = $_SESSION['PlayerID'];
$conn = sqlsrv_connect($serverName, $connectionOptions);
// ==========================
// Saldo de moedas
// ==========================
$stmtMoedas = sqlsrv_query($conn, "SELECT MoedaMumu FROM dbo.Players WHERE PlayerID = ?", [$playerID]);
if($stmtMoedas === false){
    die("Erro ao buscar saldo: " . print_r(sqlsrv_errors(), true));
}
$player = sqlsrv_fetch_array($stmtMoedas, SQLSRV_FETCH_ASSOC);
$saldo = intval($player['MoedaMumu'] ?? 0);


// ==========================
// Nome do personagem
// ==========================
$stmtChar = sqlsrv_query($conn, "SELECT Name FROM dbo.Characters WHERE PlayerID = ?", [$playerID]);
if($stmtChar === false){
    die("Erro ao buscar personagem: " . print_r(sqlsrv_errors(), true));
}
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
$nomeJogador = htmlspecialchars($char['Name'] ?? 'Desconhecido');





// ==========================
// Pega itens da mochila
// ==========================
$sqlMochila = "SELECT TOP 1000 * FROM [MumuDB].[dbo].[Mochila] WHERE PlayerID = ?";
$stmtMochila = sqlsrv_query($conn, $sqlMochila, [$playerID]);

$mochila = [];
while($row = sqlsrv_fetch_array($stmtMochila, SQLSRV_FETCH_ASSOC)) {
    $row['DataAdicionado'] = $row['DataAdicionado'] ? $row['DataAdicionado']->format('Y-m-d H:i:s') : null;
    $mochila[] = $row;
}

sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
	
    <title>Mochila de <?php echo htmlspecialchars($nomeJogador); ?></title>
</head>
<body>


<nav style="display:flex; justify-content:center; align-items:center; margin:20px;">
    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>
</nav>


    <h1>Bem-vindo, <?php echo htmlspecialchars($nomeJogador); ?>!</h1>
	
	<p>Jogador: <strong><?= $nomeJogador ?></strong> | Saldo: <span id="saldo"><?= $saldo ?> 💰</span></p>



    <p>Saldo: <?php echo number_format($saldo, 0, ',', '.'); ?></p>

    <h2>Itens da Mochila:</h2>
    <?php if(count($mochila) > 0): ?>
        <ul>
        <?php foreach($mochila as $item): ?>
            <li>
                <?php echo htmlspecialchars($item['Nome']); ?> 
                (Qtd: <?php echo $item['Quantidade']; ?>, Adicionado em: <?php echo $item['DataAdicionado']; ?>)
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Sua mochila está vazia.</p>
    <?php endif; ?>
</body>
</html>
