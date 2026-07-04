<?php
// db.php - conexão com SQL Server
$serverName = "localhost";
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

// AJAX para ações nos itens
if(isset($_POST['action']) && isset($_POST['itemID'])){
    $itemID = intval($_POST['itemID']);
    $action = $_POST['action'];
    $response = [];

    $sqlItem = "SELECT Quantidade, PodeUsar, PodeMarcarLixo, PodeEnviarArmazem, PodeSoltar, Nome 
                FROM [MumuDB].[dbo].[Items] WHERE ItemID = ?";
    $stmtItem = sqlsrv_query($conn, $sqlItem, [$itemID]);
    $item = sqlsrv_fetch_array($stmtItem, SQLSRV_FETCH_ASSOC);

    if(!$item){
        echo json_encode(['erro'=>'Item não encontrado']);
        exit;
    }

    switch($action){
        case 'usar':
            if($item['PodeUsar'] && $item['Quantidade'] > 0){
                $novaQtd = max(0, $item['Quantidade'] - 1);
                sqlsrv_query($conn, "UPDATE [MumuDB].[dbo].[Items] SET Quantidade = ? WHERE ItemID = ?", [$novaQtd, $itemID]);
                $response['mensagem'] = "{$item['Nome']} usado!";
                $response['Quantidade'] = $novaQtd;
                if($novaQtd <= 0){
                    sqlsrv_query($conn, "UPDATE [MumuDB].[dbo].[Items] SET PodeUsar = 0 WHERE ItemID = ?", [$itemID]);
                }
            } else {
                $response['mensagem'] = "{$item['Nome']} não pode ser usado!";
            }
            break;
        case 'lixo':
            $response['mensagem'] = "{$item['Nome']} marcado como lixo!";
            sqlsrv_query($conn, "UPDATE [MumuDB].[dbo].[Items] SET PodeMarcarLixo = 0 WHERE ItemID = ?", [$itemID]);
            break;
        case 'armazem':
            $response['mensagem'] = "{$item['Nome']} enviado para armazém!";
            sqlsrv_query($conn, "UPDATE [MumuDB].[dbo].[Items] SET PodeEnviarArmazem = 0 WHERE ItemID = ?", [$itemID]);
            break;
        case 'soltar':
            $response['mensagem'] = "{$item['Nome']} solto!";
            sqlsrv_query($conn, "UPDATE [MumuDB].[dbo].[Items] SET PodeSoltar = 0 WHERE ItemID = ?", [$itemID]);
            break;
        default:
            $response['mensagem'] = 'Ação inválida!';
            break;
    }

    echo json_encode($response);
    exit;
}

// Listar todos os itens
$sql = "SELECT TOP 1000 [ItemID],
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
$items = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    if($row['DataAdquirido'] instanceof DateTime){
        $row['DataAdquirido'] = $row['DataAdquirido']->format('d/m/Y');
    }
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Itens RPG</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
body { font-family: 'Orbitron', sans-serif; background:#111; color:#fff; display:flex; flex-wrap:wrap; justify-content:center; padding:50px;}
.item-box { background: linear-gradient(145deg, #222, #333); border:2px solid #0f0; border-radius:12px; padding:20px; width:300px; box-shadow:0 0 15px #0f0; text-align:center; margin:10px;}
.item-box h2 { color:#0f0; text-shadow: 0 0 8px #0f0; margin-bottom:10px; }
.item-box p { margin:5px 0; }
button { margin:5px; padding:10px 15px; border:none; border-radius:8px; font-weight:bold; cursor:pointer; transition:0.2s; font-family:inherit; }
button:hover { transform: scale(1.1); }
.usar { background:#0f0; color:#000; }
.lixo { background:#f00; color:#fff; }
.armazem { background:#00f; color:#fff; }
.soltar { background:#ff0; color:#000; }
button:disabled { opacity:0.5; cursor:not-allowed; transform:none; }
#mensagem { margin:15px 0; font-size:1.1em; color:#0ff; text-shadow:0 0 5px #0ff; width:100%; text-align:center; }
</style>
</head>
<body>

<div id="mensagem"></div>

<?php foreach($items as $item): ?>
<div class="item-box" id="item-<?= $item['ItemID'] ?>">
    <h2><?= htmlspecialchars($item['Nome']) ?> (<?= $item['Quantidade'] ?>)</h2>
    <p><strong>Que faz:</strong> <?= htmlspecialchars($item['Descricao']) ?></p>
    <p><strong>Adquirido:</strong> <?= htmlspecialchars($item['DataAdquirido']) ?></p>
    <p><strong>Utilizado por:</strong> <?= htmlspecialchars($item['UsadoPor']) ?></p>

    <button class="acao usar" data-id="<?= $item['ItemID'] ?>" data-action="usar" <?= !$item['PodeUsar'] ? 'disabled' : '' ?>>🧪 Usar</button>
    <button class="acao lixo" data-id="<?= $item['ItemID'] ?>" data-action="lixo" <?= !$item['PodeMarcarLixo'] ? 'disabled' : '' ?>>🗑 Marcar como lixo</button>
    <button class="acao armazem" data-id="<?= $item['ItemID'] ?>" data-action="armazem" <?= !$item['PodeEnviarArmazem'] ? 'disabled' : '' ?>>🏦 Enviar para armazém</button>
    <button class="acao soltar" data-id="<?= $item['ItemID'] ?>" data-action="soltar" <?= !$item['PodeSoltar'] ? 'disabled' : '' ?>>💨 Soltar</button>
</div>
<?php endforeach; ?>

<script>
$(document).ready(function(){
    $('.acao').click(function(){
        var btn = $(this);
        var itemID = btn.data('id');
        var action = btn.data('action');

        $.post('', { itemID: itemID, action: action }, function(data){
            $('#mensagem').text(data.mensagem);
            var box = $('#item-' + itemID);

            if(data.Quantidade !== undefined){
                box.find('h2').text(box.find('h2').text().split('(')[0] + '('+data.Quantidade+')');
                if(data.Quantidade <= 0) box.find('[data-action="usar"]').prop('disabled', true);
            }
            if(data.PodeMarcarLixo !== undefined) box.find('[data-action="lixo"]').prop('disabled', true);
            if(data.PodeEnviarArmazem !== undefined) box.find('[data-action="armazem"]').prop('disabled', true);
            if(data.PodeSoltar !== undefined) box.find('[data-action="soltar"]').prop('disabled', true);

        }, 'json');
    });
});
</script>

</body>
</html>
