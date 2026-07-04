<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];
$mensagem = "";

// --- Excluir personagem ---
if (isset($_GET['delete'])) {
    $charID = intval($_GET['delete']); // ID do personagem

    $sql = "DELETE FROM Characters WHERE CharID = ? AND PlayerID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$charID, $playerID]);

    if ($stmt) {
        $mensagem = "🗑️ Personagem excluído com sucesso!";
    } else {
        $mensagem = "❌ Erro ao excluir: " . print_r(sqlsrv_errors(), true);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Personagens - Mumu</title>
    <style>
        body { background:#222; color:#f1f1f1; font-family:Arial; text-align:center; padding:20px; }
        h2 { color:#ffcc00; }
        a { color:orange; text-decoration:none; margin:5px; }
        .card {
            background:#333;
            padding:10px;
            margin:10px auto;
            width:60%;
            border-radius:6px;
        }
        .delete-btn {
            color:#fff;
            background:#a00;
            padding:5px 10px;
            border-radius:4px;
            text-decoration:none;
        }
        .delete-btn:hover { background:#f00; }
    </style>
</head>
<body>
<nav style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <form method="post" style="margin:0; display:flex; gap:10px;">
        <button type="submit" class="btn" name="refresh">🔄 Atualizar</button>
        <a href="dashboard.php" class="btn">⬅️ Voltar</a>
    </form>
</nav>

    <h2>👥 Seus Personagens</h2>

    <?php if($mensagem) echo "<p style='color:orange;'>$mensagem</p>"; ?>

    <?php
    $sql = "SELECT * FROM Characters WHERE PlayerID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$playerID]);

    if($stmt && sqlsrv_has_rows($stmt)){
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            echo "<div class='card'>
                    <p><strong>Nome:</strong> {$row['Name']} | 
                       <strong>Classe:</strong> {$row['Class']} | 
                       <strong>Level:</strong> {$row['Level']}</p>
                    <a class='delete-btn' href='del_personagem.php?delete={$row['CharID']}' 
                       onclick='return confirm(\"Tem certeza que deseja excluir o personagem {$row['Name']}?\");'>
                       🗑️ Excluir
                    </a>
                  </div>";
        }
    } else {
        echo "<p>Nenhum personagem encontrado.</p>";
    }
    ?>
    <br>
    <a href="dashboard.php">⬅️ Voltar</a>
</body>
</html>
