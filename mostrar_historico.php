<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    echo "⛔ Faça login primeiro.";
    exit;
}

$playerID = $_SESSION['PlayerID'];

// ==========================
// Histórico de Fazenda (compra/venda de sementes/frutas)
// ==========================
$sqlFazenda = "SELECT Acao, NomeFruta AS Item, Quantidade, DataRegistro 
               FROM dbo.HistoricoFazenda 
               WHERE PlayerID = ?";

$stmtFazenda = sqlsrv_query($conn, $sqlFazenda, [$playerID]);
if($stmtFazenda === false){
    die("Erro ao buscar histórico de fazenda: " . print_r(sqlsrv_errors(), true));
}

$historico = [];
while($row = sqlsrv_fetch_array($stmtFazenda, SQLSRV_FETCH_ASSOC)){
    $historico[] = $row;
}

// ==========================
// Histórico de Trocas
// ==========================
$sqlTroca = "SELECT 'Troca Enviada' AS Acao, CONCAT('Para PlayerID ', ParaPlayerID) AS Item, Quantidade, DataRegistro 
             FROM dbo.HistoricoTroca WHERE DePlayerID = ?
             UNION ALL
             SELECT 'Troca Recebida', CONCAT('De PlayerID ', DePlayerID), Quantidade, DataRegistro
             FROM dbo.HistoricoTroca WHERE ParaPlayerID = ?";

$stmtTroca = sqlsrv_query($conn, $sqlTroca, [$playerID, $playerID]);
if($stmtTroca === false){
    die("Erro ao buscar histórico de trocas: " . print_r(sqlsrv_errors(), true));
}

while($row = sqlsrv_fetch_array($stmtTroca, SQLSRV_FETCH_ASSOC)){
    $historico[] = $row;
}

// ==========================
// Ordena por data descendente
// ==========================
usort($historico, function($a, $b){
    $timeA = strtotime($a['DataRegistro']->format('Y-m-d H:i:s'));
    $timeB = strtotime($b['DataRegistro']->format('Y-m-d H:i:s'));
    return $timeB - $timeA;
});

?>

<?php if(empty($historico)): ?>
<p style="color:#fff;">Nenhuma transação encontrada.</p>
<?php else: ?>
<table border="1" style="width:100%; border-collapse:collapse; margin-top:10px; color:#fff;">
<tr style="background:#3498db; color:#fff;">
<th>Ação</th>
<th>Item / Destino</th>
<th>Quantidade</th>
<th>Data / Hora</th>
</tr>
<?php foreach($historico as $h): ?>
<tr>
<td><?=htmlspecialchars($h['Acao'])?></td>
<td><?=htmlspecialchars($h['Item'])?></td>
<td><?=htmlspecialchars($h['Quantidade'])?></td>
<td><?= $h['DataRegistro']->format('d/m/Y H:i:s') ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
