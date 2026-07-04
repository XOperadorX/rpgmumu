<?php
session_start();
include "db.php"; // conexão com SQL Server

header('Content-Type: application/json');

if(!isset($_SESSION['PlayerID'])){
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

$playerID = $_SESSION['PlayerID'];

// Recebe JSON do fetch
$input = json_decode(file_get_contents('php://input'), true);
$itemID = intval($input['ItemID'] ?? 0);
$acao = $input['Acao'] ?? '';

if($itemID <= 0 || !in_array($acao, ['usar','soltar','enviar'])){
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

// ==========================
// Consulta item para validação
// ==========================
$sqlCheck = "SELECT * FROM [MumuDB].[dbo].[Items] WHERE [ItemID] = ? AND [PlayerID] = ?";
$paramsCheck = [$itemID, $playerID];
$stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);

if($stmtCheck === false || ($item = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) === null){
    echo json_encode(['success' => false, 'message' => 'Item não encontrado.']);
    exit;
}

// ==========================
// Executa ação
// ==========================
switch($acao){
    case 'usar':
        if(!$item['PodeUsar']){
            echo json_encode(['success' => false, 'message' => 'Item não pode ser usado.']);
            exit;
        }

        // Se quantidade > 1, apenas decrementa
        if($item['Quantidade'] > 1){
            $sqlUpdate = "UPDATE [MumuDB].[dbo].[Items] SET Quantidade = Quantidade - 1, UsadoPor = ?, DataAdquirido = GETDATE() WHERE ItemID = ?";
            $paramsUpdate = [$playerID, $itemID];
        } else {
            // Se for 1, remove o item
            $sqlUpdate = "DELETE FROM [MumuDB].[dbo].[Items] WHERE ItemID = ?";
            $paramsUpdate = [$itemID];
        }
        break;

    case 'soltar':
        if(!$item['PodeSoltar']){
            echo json_encode(['success' => false, 'message' => 'Item não pode ser solto.']);
            exit;
        }
        // Decrementa quantidade se > 1 ou remove
        if($item['Quantidade'] > 1){
            $sqlUpdate = "UPDATE [MumuDB].[dbo].[Items] SET Quantidade = Quantidade - 1 WHERE ItemID = ?";
            $paramsUpdate = [$itemID];
        } else {
            $sqlUpdate = "DELETE FROM [MumuDB].[dbo].[Items] WHERE ItemID = ?";
            $paramsUpdate = [$itemID];
        }
        break;

    case 'enviar':
        if(!$item['PodeEnviarArmazem']){
            echo json_encode(['success' => false, 'message' => 'Item não pode ser enviado.']);
            exit;
        }
        // Marca como enviado para armazém
        $sqlUpdate = "UPDATE [MumuDB].[dbo].[Items] SET UsadoPor = 'ARMAZEM' WHERE ItemID = ?";
        $paramsUpdate = [$itemID];
        break;
}

// ==========================
// Executa no banco
// ==========================
$stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
if($stmtUpdate === false){
    echo json_encode(['success' => false, 'message' => 'Erro ao executar ação.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Ação realizada com sucesso.']);
?>
