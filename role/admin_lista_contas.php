<?php
session_start();
include "db.php";
//include "check_ban.php";

function checkAdmin() {
    if(!isset($_SESSION['PlayerID']) || !isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin'){
        die("Acesso negado. Apenas admins podem acessar.");
    }
}
checkAdmin();

$message = '';

// ===== Excluir conta se receber POST =====
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['PlayerID'])){
    $playerID = intval($_POST['PlayerID']);

    if($playerID > 0){
        try {
            $conn->beginTransaction();

            $sql = "DELETE FROM Inventario WHERE CharID IN (SELECT CharID FROM Personagens WHERE PlayerID = ?)";
            $stmt = $conn->prepare($sql); $stmt->execute([$playerID]);

            $sql = "DELETE FROM Personagens WHERE PlayerID = ?";
            $stmt = $conn->prepare($sql); $stmt->execute([$playerID]);

            $sql = "DELETE FROM Dung WHERE PlayerID = ?";
            $stmt = $conn->prepare($sql); $stmt->execute([$playerID]);

            $sql = "DELETE FROM Turno WHERE PlayerID = ?";
            $stmt = $conn->prepare($sql); $stmt->execute([$playerID]);

            $sql = "DELETE FROM BankAccounts WHERE PlayerID = ?";
            $stmt = $conn->prepare($sql); $stmt->execute([$playerID]);

            $sql = "DELETE FROM Players WHERE PlayerID = ?";
            $stmt = $conn->prepare($sql); $stmt->execute([$playerID]);

            $conn->commit();
            $message = "Conta ID $playerID excluída com sucesso.";

        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Erro ao excluir conta ID $playerID: " . $e->getMessage();
        }
    } else {
        $message = "ID inválido.";
    }
}

// ===== Buscar jogadores ativos com contagem de personagens e itens =====
$sql = "SELECT 
            p.PlayerID, p.Username, p.MoedaMumu, p.CreatedAt,
            (SELECT COUNT(*) FROM Personagens WHERE PlayerID = p.PlayerID) AS totalChars,
            (SELECT COUNT(*) FROM Inventario WHERE CharID IN (SELECT CharID FROM Personagens WHERE PlayerID = p.PlayerID)) AS totalItens
        FROM Players p
        ORDER BY p.PlayerID ASC";
$stmt = $conn->query($sql);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
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
        <td><?php echo htmlspecialchars($player['Username']); ?></td>
        <td><?php echo $player['MoedaMumu']; ?></td>
        <td><?php echo $player['CreatedAt']; ?></td>
        <td><?php echo $player['totalChars']; ?></td>
        <td><?php echo $player['totalItens']; ?></td>
        <td>
            <form method="post" style="margin:0;">
                <input type="hidden" name="PlayerID" value="<?php echo $player['PlayerID']; ?>">
                <button type="submit" onclick="return confirm('Tem certeza que deseja excluir esta conta?\nUsername: <?php echo addslashes($player['Username']); ?>\nPersonagens: <?php echo $player['totalChars']; ?>\nItens: <?php echo $player['totalItens']; ?>');">
                    ❌ Excluir
                </button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
