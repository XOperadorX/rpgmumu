<?php
session_start();
include "db.php"; // Conexão PDO

header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['error'=>'Acesso negado']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// Recebe JSON do fetch
$input = json_decode(file_get_contents('php://input'), true);
$tipo = $input['tipo'] ?? '';
$valor = floatval($input['valor'] ?? 0);

if($valor <= 0){
    echo json_encode(['msg'=>'Valor inválido']);
    exit;
}

// Pega saldos atuais do jogador
$stmt = $conn->prepare("SELECT MoedaMumu, Corrente, Poupanca, Pix, Real FROM Players WHERE PlayerID=?");
$stmt->execute([$playerID]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$player){
    echo json_encode(['msg'=>'Jogador não encontrado']);
    exit;
}

// Função auxiliar para atualizar saldo e histórico
function atualizarSaldo($conn, $playerID, $coluna, $novoValor, $tipoOperacao, $valorTransf){
    $stmt = $conn->prepare("UPDATE Players SET $coluna=? WHERE PlayerID=?");
    $stmt->execute([$novoValor, $playerID]);

    $stmtHist = $conn->prepare("INSERT INTO BankHistory (PlayerID, Tipo, Valor, Data) VALUES (?, ?, ?, GETDATE())");
    $stmtHist->execute([$playerID, $tipoOperacao, $valorTransf]);
}

// Processa cada tipo de transferência
$msg = '';
switch($tipo){
    case 'mumuToCorrente':
        if($player['MoedaMumu'] >= $valor){
            atualizarSaldo($conn, $playerID, 'MoedaMumu', $player['MoedaMumu'] - $valor, 'Mumu→Corrente', $valor);
            atualizarSaldo($conn, $playerID, 'Corrente', $player['Corrente'] + $valor, 'Mumu→Corrente', $valor);
            $msg = "💰 Transferência concluída!";
        } else $msg = "Saldo insuficiente!";
        break;

    case 'correnteToPoupanca':
        if($player['Corrente'] >= $valor){
            atualizarSaldo($conn, $playerID, 'Corrente', $player['Corrente'] - $valor, 'Corrente→Poupança', $valor);
            atualizarSaldo($conn, $playerID, 'Poupanca', $player['Poupanca'] + $valor, 'Corrente→Poupança', $valor);
            $msg = "💰 Transferência concluída!";
        } else $msg = "Saldo insuficiente!";
        break;

    case 'poupancaToPix':
        if($player['Poupanca'] >= $valor){
            atualizarSaldo($conn, $playerID, 'Poupanca', $player['Poupanca'] - $valor, 'Poupança→Pix', $valor);
            atualizarSaldo($conn, $playerID, 'Pix', $player['Pix'] + $valor, 'Poupança→Pix', $valor);
            $msg = "💰 Transferência concluída!";
        } else $msg = "Saldo insuficiente!";
        break;

    case 'pixToReal':
        if($player['Pix'] >= $valor){
            atualizarSaldo($conn, $playerID, 'Pix', $player['Pix'] - $valor, 'Pix→Real', $valor);
            atualizarSaldo($conn, $playerID, 'Real', $player['Real'] + $valor, 'Pix→Real', $valor);
            $msg = "💰 Conversão concluída!";
        } else $msg = "Saldo insuficiente!";
        break;

    default:
        $msg = "Tipo de operação inválido!";
}

echo json_encode(['msg'=>$msg]);
