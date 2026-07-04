<?php
if (!isset($conn)) { include "db.php"; }
if (!isset($_SESSION)) { session_start(); }

header('Content-Type: application/json');

$playerID = $_SESSION['PlayerID'] ?? null;
if(!$playerID){ echo json_encode(['error'=>"⛔ Faça login"]); exit; }

try {
    // Últimas 10 transações do jogador
    $stmt = sqlsrv_query($conn, "
        SELECT TOP 10 
            ht.DataHora, ht.Tipo, a.Nome as Item, ht.Quantidade, ht.PrecoUnit, ht.Total
        FROM HistoricoTransacoes ht
        INNER JOIN Ativos a ON ht.ItemID = a.AtivoID
        WHERE ht.CompradorID = ? OR ht.VendedorID = ?
        ORDER BY ht.DataHora DESC
    ", [$playerID, $playerID]);

    $tabela = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $tabela[] = [
            'datahora'=> $row['DataHora']->format('d/m/Y H:i:s'),
            'acao'=> $row['Tipo'],
            'item'=> $row['Item'],
            'qtd'=> $row['Quantidade'],
            'preco_unit'=> $row['PrecoUnit'],
            'total'=> $row['Total']
        ];
    }

    // Dados para gráficos: últimos 20 preços de cada ativo
    $stmt2 = sqlsrv_query($conn, "SELECT Nome, PrecoAtual FROM Ativos ORDER BY AtivoID ASC");
    $grafico = [];
    while($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)){
        $nome = $row['Nome'];
        $preco = floatval($row['PrecoAtual']);
        // Criar array de exemplo com 20 valores iguais para o gráfico inicial
        $grafico[$nome] = array_fill(0, 20, ['preco'=>$preco]);
    }

    echo json_encode(['tabela'=>$tabela, 'grafico'=>$grafico]);

} catch(Exception $e){
    echo json_encode(['error'=>"Erro ao buscar histórico."]);
}
