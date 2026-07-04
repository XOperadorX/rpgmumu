<?php
// db.php - conexão com SQL Server
$serverName = "localhost"; // ou IP do servidor
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",
    "PWD" => "Xer@x123456",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// AJAX: ações nos itens
if(isset($_POST['action']) && isset($_POST['itemID'])){
    $itemID = intval($_POST['itemID']);
    $action = $_POST['action'];
    $response = [];

    // Buscar dados atuais do item
    $sqlItem = "SELECT Quantidade, PodeUsar, PodeMarcarLixo, PodeEnviarArmazem, PodeSoltar FROM [MumuDB].[dbo].[Items] WHERE ItemID = ?";
    $stmtItem = sqlsrv_query($conn, $sqlItem, [$itemID]);
    if($stmtItem === false){
        echo json_encode(['erro' => 'Erro ao buscar item']);
        exit;
    }
    $item = sqlsrv_fetch_array($stmtItem, SQLSRV_FETCH_ASSOC);

    switch($action){
        case 'usar':
            if($item['PodeUsar']){
                $novaQtd = max(0, $item['Quantidade'] - 1);
                $sqlUpdate = "UPDATE [MumuDB].[dbo].[Items] SET Quantidade = ? WHERE ItemID = ?";
                sqlsrv_query($conn, $sqlUpdate, [$novaQtd, $itemID]);
                $response['mensagem'] = "Item $itemID usado!";
                $response['Quantidade'] = $novaQtd;
            } else {
                $response['mensagem'] = "Item $itemID não pode ser usado!";
            }
            break;
        case 'lixo':
            $response['mensagem'] = "Item $itemID marcado como lixo!";
            $response['PodeMarcarLixo'] = 0;
            $sqlUpdate = "UPDATE [MumuDB].[dbo].[Items] SET PodeMarcarLixo = 0 WHERE ItemID = ?";
            sqlsrv_query($conn, $sqlUpdate, [$itemID]);
            break;
        case 'armazem':
            $response['mensagem'] = "Item $itemID enviado para armazém!";
            $response['PodeEnviarArmazem'] = 0;
            $sqlUpdate = "UPDATE [MumuDB].[dbo].[Items] SET PodeEnviarArmazem = 0 WHERE ItemID = ?";
            sqlsrv_query($conn, $sqlUpdate, [$itemID]);
            break;
        case 'soltar':
            $response['mensagem'] = "Item $itemID solto!";
            $response['PodeSoltar'] = 0;
            $sqlUpdate = "UPDATE [MumuDB].[dbo].[Items] SET PodeSoltar = 0 WHERE ItemID = ?";
            sqlsrv_query($conn, $sqlUpdate, [$itemID]);
            break;
    }

    echo json_encode($response);
    exit;
}

// Query para listar itens
$sql = "SELECT TOP 1000 
      [ItemID],
      [CharID],
      [Nome],
      [PlayerID],
      [Quantidade],
      [Descricao],
      [DataAdquirido],
      [UsadoPor],
      [PodeUsar],
      [PodeMarcarLixo],
      [PodeEnviarArmazem],
      [PodeSoltar]
FROM [MumuDB].[dbo].[Items]";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Itens RPG</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ccc; padding: 5px; text-align: center; }
button { margin: 2px; }
#mensagem { margin: 10px 0; color: green; font-weight: bold; }
.disabled { opacity: 0.5; pointer-events: none; }
</style>
</head>
<body>

<div id="mensagem"></div>

<table>
    <tr>
        <th>ItemID</th>
        <th>CharID</th>
        <th>Nome</th>
        <th>PlayerID</th>
        <th>Quantidade</th>
        <th>Descricao</th>
        <th>DataAdquirido</th>
        <th>UsadoPor</th>
        <th>PodeUsar</th>
        <th>PodeMarcarLixo</th>
        <th>PodeEnviarArmazem</th>
        <th>PodeSoltar</th>
        <th>Ações</th>
    </tr>
    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
    <tr id="item-<?= $row['ItemID'] ?>">
        <td><?= htmlspecialchars($row['ItemID']) ?></td>
        <td><?= htmlspecialchars($row['CharID']) ?></td>
        <td><?= htmlspecialchars($row['Nome']) ?></td>
        <td><?= htmlspecialchars($row['PlayerID']) ?></td>
        <td class="quantidade"><?= $row['Quantidade'] ?></td>
        <td><?= htmlspecialchars($row['Descricao']) ?></td>
		<td><?= isset($row['DataAdquirido']) ? htmlspecialchars($row['DataAdquirido']->format('Y-m-d H:i:s')) : '' ?></td>

        <td><?= htmlspecialchars($row['UsadoPor']) ?></td>
        <td class="podeUsar"><?= $row['PodeUsar'] ? 'Sim' : 'Não' ?></td>
        <td class="podeMarcarLixo"><?= $row['PodeMarcarLixo'] ? 'Sim' : 'Não' ?></td>
        <td class="podeEnviarArmazem"><?= $row['PodeEnviarArmazem'] ? 'Sim' : 'Não' ?></td>
        <td class="podeSoltar"><?= $row['PodeSoltar'] ? 'Sim' : 'Não' ?></td>
        <td>
            <button class="acao" data-id="<?= $row['ItemID'] ?>" data-action="usar" <?= !$row['PodeUsar'] ? 'disabled' : '' ?>>Usar</button>
            <button class="acao" data-id="<?= $row['ItemID'] ?>" data-action="lixo" <?= !$row['PodeMarcarLixo'] ? 'disabled' : '' ?>>Lixo</button>
            <button class="acao" data-id="<?= $row['ItemID'] ?>" data-action="armazem" <?= !$row['PodeEnviarArmazem'] ? 'disabled' : '' ?>>Armazém</button>
            <button class="acao" data-id="<?= $row['ItemID'] ?>" data-action="soltar" <?= !$row['PodeSoltar'] ? 'disabled' : '' ?>>Soltar</button>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<script>
$(document).ready(function(){
    $('.acao').click(function(){
        var btn = $(this);
        var itemID = btn.data('id');
        var action = btn.data('action');

        $.post('', { itemID: itemID, action: action }, function(data){
            $('#mensagem').text(data.mensagem);

            var row = $('#item-' + itemID);

            // Atualizar quantidade ou status conforme ação
            if(data.Quantidade !== undefined){
                row.find('.quantidade').text(data.Quantidade);
            }
            if(data.PodeMarcarLixo !== undefined){
                row.find('.podeMarcarLixo').text(data.PodeMarcarLixo ? 'Sim' : 'Não');
                if(!data.PodeMarcarLixo) row.find('[data-action="lixo"]').prop('disabled', true);
            }
            if(data.PodeEnviarArmazem !== undefined){
                row.find('.podeEnviarArmazem').text(data.PodeEnviarArmazem ? 'Sim' : 'Não');
                if(!data.PodeEnviarArmazem) row.find('[data-action="armazem"]').prop('disabled', true);
            }
            if(data.PodeSoltar !== undefined){
                row.find('.podeSoltar').text(data.PodeSoltar ? 'Sim' : 'Não');
                if(!data.PodeSoltar) row.find('[data-action="soltar"]').prop('disabled', true);
            }
            if(action === 'usar' && data.Quantidade !== undefined && data.Quantidade <= 0){
                row.find('[data-action="usar"]').prop('disabled', true);
                row.find('.podeUsar').text('Não');
            }

        }, 'json');
    });
});
</script>

</body>
</html>

<?php
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
