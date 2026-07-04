<?php
if (!isset($conn)) { include "db.php"; }
if (!isset($_SESSION)) { session_start(); }

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$playerID = $_SESSION['PlayerID'] ?? 0;

// Função para buscar últimas transações
function buscarHistorico($conn) {
    $stmt = sqlsrv_query($conn, "
        SELECT TOP 10 h.DataHora, h.Tipo, a.Nome, h.Quantidade, h.PrecoUnit, h.Total
        FROM HistoricoTransacoes h
        JOIN Ativos a ON h.ItemID = a.AtivoID
        ORDER BY h.DataHora DESC
    ");
    $tabela = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $tabela[] = [
            'datahora'   => $row['DataHora']->format('d/m/Y H:i:s'),
            'acao'       => $row['Tipo'],
            'item'       => $row['Nome'],
            'qtd'        => $row['Quantidade'],
            'preco_unit' => $row['PrecoUnit'],
            'total'      => $row['Total']
        ];
    }
    return $tabela;
}

// Loop SSE
while (true) {
    $historico = buscarHistorico($conn);
    echo "data: " . json_encode(['tabela' => $historico]) . "\n\n";
    ob_flush();
    flush();
    sleep(2); // envia atualização a cada 2 segundos
}
