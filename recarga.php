<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['PlayerID'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Não autenticado']);
    exit;
}

include "db.php";

$playerID = $_SESSION['PlayerID'];
$valorPix = 2000; // valor a cobrar
$agora = new DateTime();

try {
    // ==========================
    // Inicia transação
    // ==========================
    if (!sqlsrv_begin_transaction($conn)) {
        throw new Exception('Não foi possível iniciar transação');
    }

    // ==========================
    // Bloqueia e busca info do jogador
    // ==========================
    $stmt = sqlsrv_query(
        $conn,
        "SELECT RecargaExpiraEm FROM Players WITH (UPDLOCK, ROWLOCK) WHERE PlayerID = ?",
        [$playerID]
    );
    if ($stmt === false) throw new Exception('Erro ao buscar jogador');

    $info = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$info) throw new Exception('Jogador não encontrado');

    $recargaExpira = $info['RecargaExpiraEm'] ?? null;

    // ==========================
    // Checa se recarga já ativa
    // ==========================
    if ($recargaExpira) {
        $expira = ($recargaExpira instanceof DateTime) ? $recargaExpira : new DateTime($recargaExpira);
        if ($agora < $expira) {
            $restante = $expira->getTimestamp() - $agora->getTimestamp();
            sqlsrv_rollback($conn);
            echo json_encode([
                'status' => 'aguarde',
                'mensagem' => 'Recarga ativa!',
                'restante_segundos' => $restante,
                'expira_em' => $expira->format('Y-m-d H:i:s')
            ]);
            exit;
        }
    }

    // ==========================
    // Bloqueia e busca saldo Pix
    // ==========================
    $stmt = sqlsrv_query(
        $conn,
        "SELECT Pix FROM BankAccounts WITH (UPDLOCK, ROWLOCK) WHERE PlayerID = ?",
        [$playerID]
    );
    if ($stmt === false) throw new Exception('Erro ao buscar saldo Pix');

    $conta = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$conta) throw new Exception('Conta Pix não encontrada');

    $saldoPix = $conta['Pix'] ?? 0;

    if ($saldoPix < $valorPix) {
        sqlsrv_rollback($conn);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => "Saldo insuficiente para recarga (Pix necessário: $valorPix)"
        ]);
        exit;
    }

    // ==========================
    // Debita Pix
    // ==========================
    $stmt = sqlsrv_query($conn, "UPDATE BankAccounts SET Pix = Pix - ? WHERE PlayerID = ?", [$valorPix, $playerID]);
    if ($stmt === false) throw new Exception('Falha ao cobrar Pix');

    // ==========================
    // Ativa recarga por 24h
    // ==========================
    $novaExpira = clone $agora;
    $novaExpira->modify('+1 day');

    $stmt = sqlsrv_query(
        $conn,
        "UPDATE Players SET RecargaExpiraEm = ? WHERE PlayerID = ?",
        [$novaExpira->format('Y-m-d H:i:s'), $playerID]
    );
    if ($stmt === false) throw new Exception('Falha ao ativar recarga');

    // ==========================
    // Commit da transação
    // ==========================
    if (!sqlsrv_commit($conn)) throw new Exception('Falha ao confirmar transação');

    $restante = $novaExpira->getTimestamp() - $agora->getTimestamp();
    
    // ==========================
    // Retorna JSON completo
    // ==========================
    echo json_encode([
        'status' => 'ok',
        'mensagem' => "Recarga ativada por 24h! R$ $valorPix cobrado do Pix.",
        'restante_segundos' => $restante,
        'expira_em' => $novaExpira->format('Y-m-d H:i:s'),
        'restante_formatado' => gmdate('H:i:s', $restante) // horas:minutos:segundos
    ]);

} catch (Exception $e) {
    if (isset($conn)) sqlsrv_rollback($conn);
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
    exit;
}
