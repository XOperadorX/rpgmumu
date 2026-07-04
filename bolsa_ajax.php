<?php
if(!isset($conn)) include "db.php";
if(!isset($_SESSION)) session_start();

header('Content-Type: application/json');

$playerID = $_SESSION['PlayerID'] ?? null;
if(!$playerID){ echo json_encode(['error'=>"⛔ Faça login"]); exit; }

$acao = $_POST['acao'] ?? '';
$ativoNome = $_POST['ativo'] ?? '';
$quantidade = max(1, intval($_POST['quantidade'] ?? 1));

if(!$acao || !$ativoNome){ echo json_encode(['error'=>"Dados inválidos"]); exit; }

// Pega preço atual do ativo
$stmt = sqlsrv_query($conn, "SELECT AtivoID, PrecoAtual, UltimaVariacao FROM Ativos WHERE Nome=?", [$ativoNome]);
if(!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))){
    echo json_encode(['error'=>"Ativo inválido"]); exit;
}

$ativoID = $row['AtivoID'];
$precoAtual = floatval($row['PrecoAtual']);
$variacao = intval($row['UltimaVariacao']);
$total = $precoAtual * $quantidade;

// Saldo e carteira do jogador
$stmt = sqlsrv_query($conn,"SELECT MoedaMumu, CarteiraJSON FROM Players WHERE PlayerID=?", [$playerID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$saldo = floatval($row['MoedaMumu']);
$carteira = !empty($row['CarteiraJSON']) ? json_decode($row['CarteiraJSON'], true) : [];
if(!isset($carteira[$ativoNome])) $carteira[$ativoNome] = 0;

// Compra ou venda
if($acao==='comprar'){
    if($saldo < $total){ echo json_encode(['error'=>"Saldo insuficiente"]); exit; }
    $saldo -= $total;
    $carteira[$ativoNome] += $quantidade;
    $msg = "Comprou $quantidade de $ativoNome";
    $variacaoMsg = +1;
}elseif($acao==='vender'){
    if($carteira[$ativoNome] < $quantidade){ echo json_encode(['error'=>"Quantidade insuficiente"]); exit; }
    $saldo += $total;
    $carteira[$ativoNome] -= $quantidade;
    $msg = "Vendeu $quantidade de $ativoNome";
    $variacaoMsg = -1;
}else{
    echo json_encode(['error'=>"Ação inválida"]); exit;
}

// Atualiza Players
sqlsrv_query($conn,"UPDATE Players SET MoedaMumu=?, CarteiraJSON=? WHERE PlayerID=?", [
    $saldo,
    json_encode($carteira, JSON_UNESCAPED_UNICODE),
    $playerID
]);

// Histórico
$sqlHist = "INSERT INTO HistoricoTransacoes (CompradorID,VendedorID,ItemID,Quantidade,PrecoMoedaMumu,Tipo,DataTransacao,PrecoUnit,Total,DataHora)
SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE()";
if($acao==='comprar'){
    sqlsrv_query($conn,$sqlHist, [$playerID, null, $ativoID, $quantidade, $total, 'compra', date('Y-m-d'), $precoAtual, $total]);
}else{
    sqlsrv_query($conn,$sqlHist, [null, $playerID, $ativoID, $quantidade, $total, 'venda', date('Y-m-d'), $precoAtual, $total]);
}

echo json_encode([
    'novo_saldo'=>$saldo,
    'nova_qtd'=>$carteira[$ativoNome],
    'novo_preco'=>$precoAtual,
    'msg'=>$msg,
    'variacao'=>$variacaoMsg
]);
