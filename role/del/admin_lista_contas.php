<?php
session_start();

// ===== Conexão com SQL Server =====
$serverName = "localhost";
$connectionOptions = [
    "Database" => "MumuDB",
    "Uid" => "sa",   // troque pelo seu usuário do SQL Server
    "PWD" => "Xer@x123456",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("❌ Conexão falhou: " . print_r(sqlsrv_errors(), true));
}

$message = '';

// ===== Excluir conta se receber POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['PlayerID'])) {
    $playerID = intval($_POST['PlayerID']);

    if ($playerID > 0) {

        // Começar transação
        sqlsrv_begin_transaction($conn);

        try {
            $params = [$playerID];

            // Deletar Inventário
            $sql = "DELETE FROM dbo.Inventario WHERE CharID IN (SELECT CharID FROM dbo.Personagens WHERE PlayerID = ?)";
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));

            // Deletar Personagens
            $sql = "DELETE FROM dbo.Personagens WHERE PlayerID = ?";
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));

            // Deletar Dungeons
            $sql = "DELETE FROM dbo.Dung WHERE PlayerID = ?";
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));

            // Deletar Turnos
            $sql = "DELETE FROM dbo.Turno WHERE PlayerID = ?";
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));

            // Deletar BankAccounts
            $sql = "DELETE FROM dbo.BankAccounts WHERE PlayerID = ?";
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));

            // Deletar Players
            $sql = "DELETE FROM dbo.Players WHERE PlayerID = ?";
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));

            // Commit
            sqlsrv_commit($conn);
            $message = "✅ Conta ID $playerID excluída com sucesso.";

        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            $message = "❌ Erro ao excluir conta ID $playerID: " . $e->getMessage();
        }
    } else {
        $message = "❌ ID inválido.";
    }
}

// ===== Buscar jogadores ativos com contagem de personagens e itens =====
$sql = "SELECT 
            p.PlayerID, p.Username, p.MoedaMumu, p.CreatedAt,
            (SELECT COUNT(*) FROM dbo.Personagens WHERE PlayerID = p.PlayerID) AS totalChars,
            (SELECT COUNT(*) FROM dbo.Inventario WHERE CharID IN (SELECT CharID FROM dbo.Personagens WHERE PlayerID = p.PlayerID)) AS totalItens
        FROM dbo.Players p
        ORDER BY p.PlayerID ASC";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$players = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $players[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Admin - Lista Detalhada de Contas</title>
<style>
body { font-family: Arial, sans-serif; background: #1e1e2f; color: #fff; text-align: center; padding: 50px; }
table { margin: 0 auto; border-collapse: collapse; width: 90%; }
th, td { border: 1px solid #fff; padding: 10px; }
th { background: #333; }
button { padding: 5px 10px; border-radius: 5px; border: none; cursor: pointer; background: #ff4d4d; color: #fff; }
.message { margin: 20px; font-weight: bold; }
</style>
</head>
<body>
<h1>Lista Detalhada de Contas de Jogadores</h1>

<?php if($message): ?>
    <div class="message"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
<?php endif; ?>

<table>
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>MoedaMumu</th>
        <th>Criado em</th>
        <th>Personagens</th>
        <th>Itens</th>
        <th>Ação</th>
    </tr>
    <?php foreach($players as $player): ?>
    <tr>
        <td><?php echo $player['PlayerID']; ?></td>
        <td><?php echo htmlspecialchars($player['Username'], ENT_QUOTES); ?></td>
        <td><?php echo $player['MoedaMumu']; ?></td>
        <td><?php echo $player['CreatedAt']->format('Y-m-d H:i:s'); ?></td>
        <td><?php echo $player['totalChars']; ?></td>
        <td><?php echo $player['totalItens']; ?></td>
        <td>
            <form method="post" style="margin:0;">
                <input type="hidden" name="PlayerID" value="<?php echo $player['PlayerID']; ?>">
                <button type="submit" onclick="return confirm('Tem certeza que deseja excluir esta conta?\nUsername: <?php echo htmlspecialchars($player['Username'], ENT_QUOTES); ?>\nPersonagens: <?php echo $player['totalChars']; ?>\nItens: <?php echo $player['totalItens']; ?>');">
                    ❌ Excluir
                </button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
