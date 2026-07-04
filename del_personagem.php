<?php
session_start();
include "db.php";
include "check_ban.php"; // Protege a página

if(!isset($_SESSION['PlayerID'])){
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];
$mensagem = "";
$taxaExclusao = 500;

// Processar exclusão confirmada
if(isset($_POST['confirm_delete']) && isset($_POST['char_id'])){
    $charID = intval($_POST['char_id']);

    // Verifica saldo
    $sqlSaldo = "SELECT MoedaMumu FROM Players WHERE PlayerID=?";
    $stmtSaldo = sqlsrv_query($conn, $sqlSaldo, [$playerID]);
    $saldo = 0;
    if($stmtSaldo && $rowSaldo = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)){
        $saldo = $rowSaldo['MoedaMumu'];
    }

    if($saldo < $taxaExclusao){
        $mensagem = "❌ Saldo insuficiente. É necessário $taxaExclusao MoedaMumu para excluir um personagem.";
    } else {
        sqlsrv_begin_transaction($conn);
        try {
            // Excluir itens
            $deleteItems = sqlsrv_query($conn, "DELETE FROM Items WHERE CharID=?", [$charID]);
            if($deleteItems === false) throw new Exception("Erro ao excluir itens: " . print_r(sqlsrv_errors(), true));

            // Excluir histórico
            $deleteHistorico = sqlsrv_query($conn, "DELETE FROM Historico WHERE CharID=?", [$charID]);
            if($deleteHistorico === false) throw new Exception("Erro ao excluir histórico: " . print_r(sqlsrv_errors(), true));

            // Excluir personagem
            $deleteChar = sqlsrv_query($conn, "DELETE FROM Characters WHERE CharID=? AND PlayerID=?", [$charID, $playerID]);
            if($deleteChar === false) throw new Exception("Erro ao excluir personagem: " . print_r(sqlsrv_errors(), true));

            // Subtrair taxa
            $updateSaldo = sqlsrv_query($conn, "UPDATE Players SET MoedaMumu = MoedaMumu - ? WHERE PlayerID=?", [$taxaExclusao, $playerID]);
            if($updateSaldo === false) throw new Exception("Erro ao deduzir MoedaMumu: " . print_r(sqlsrv_errors(), true));

            sqlsrv_commit($conn);
            $mensagem = "✅ Personagem excluído com sucesso! Foram debitados $taxaExclusao MoedaMumu.";
        } catch(Exception $e){
            sqlsrv_rollback($conn);
            $mensagem = "❌ Erro ao excluir personagem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Excluir Personagem</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
body {
    font-family: "Orbitron", sans-serif;
    background: radial-gradient(circle at center, #0a0a0f 60%, #020205);
    color: #0ff;
    text-align: center;
}
h2 {
    margin-top: 30px;
    text-shadow: 0 0 10px #0ff;
}
.personagem {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid #0ff;
    border-radius: 12px;
    padding: 10px;
    margin: 10px auto;
    width: 300px;
    box-shadow: 0 0 10px #0ff;
}
.btn {
    background: linear-gradient(90deg, #00ffff, #0077ff);
    border: none;
    color: #000;
    padding: 8px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}
.btn:hover {
    background: linear-gradient(90deg, #00cccc, #0055ff);
    transform: scale(1.05);
}
#popup {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.85);
    display: none;
    justify-content: center;
    align-items: center;
}
.popup-box {
    background: #0a0a12;
    border: 2px solid #0ff;
    border-radius: 15px;
    padding: 25px;
    width: 400px;
    box-shadow: 0 0 25px #0ff;
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.mensagem {
    margin-top: 20px;
    color: #0ff;
    text-shadow: 0 0 8px #00ffff;
}
</style>
</head>
<body>
<nav style="display:flex; justify-content:space-between; align-items:center; margin:20px;">
    <a href="dashboard.php" class="btn">⬅️ Voltar</a>
</nav>

<h2>🗑️ Excluir Personagem</h2>

<?php
$sql = "SELECT * FROM Characters WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);

if($stmt && sqlsrv_has_rows($stmt)){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        echo "
        <div class='personagem'>
            <strong>{$row['Name']}</strong> <br>
            Classe: {$row['Class']}<br><br>
            <button class='btn' onclick=\"abrirPopup({$row['CharID']}, '{$row['Name']}')\">Excluir</button>
        </div>";
    }
} else {
    echo "<p>Nenhum personagem encontrado.</p>";
}
?>

<div class="mensagem"><?= $mensagem ?></div>

<!-- POPUP FUTURISTA -->
<div id="popup">
    <div class="popup-box">
        <h3>⚠️ Confirmar Exclusão</h3>
        <p id="popup-text"></p>
        <p style="color:#ff6666;">💰 Custo: 500 MoedaMumu</p>
        <form method="POST">
            <input type="hidden" id="char_id" name="char_id">
            <button type="submit" name="confirm_delete" class="btn">Sim, Excluir</button>
            <button type="button" class="btn" onclick="fecharPopup()">Cancelar</button>
        </form>
    </div>
</div>

<script>
function abrirPopup(charID, nome){
    document.getElementById('popup').style.display = 'flex';
    document.getElementById('popup-text').innerHTML = `Tem certeza que deseja excluir <b>${nome}</b>?<br>Essa ação é irreversível.`;
    document.getElementById('char_id').value = charID;
}
function fecharPopup(){
    document.getElementById('popup').style.display = 'none';
}
</script>

</body>
</html>
